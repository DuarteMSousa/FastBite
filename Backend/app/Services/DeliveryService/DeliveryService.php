<?php

namespace App\Services\DeliveryService;

use App\Aspects\Transactional;
use App\Domain\Geo\GeoMath;
use App\Domain\StateMachines\Deliveries\DeliveryStateFactory;
use App\DTOs\Delivery\CreateDeliveryEventDTO;
use App\DTOs\Delivery\CreateDeliveryOfferDTO;
use App\DTOs\Delivery\UpdateDeliveryDTO;
use App\DTOs\Delivery\UpdateDeliveryOfferDTO;
use App\Enums\CourierStatus;
use App\Enums\DeliveryEventType;
use App\Enums\DeliveryOfferEventType;
use App\Enums\DeliveryOfferStatus;
use App\Enums\DeliveryStatus;
use App\Events\NotificationEventRecorded;
use App\Jobs\AssignCourierToDeliveryJob;
use App\Jobs\ExpireDeliveryOfferJob;
use App\Models\Delivery;
use App\Models\DeliveryOffer;
use App\Repositories\CourierRepository\CourierRepositoryInterface;
use App\Repositories\DeliveryRepository\DeliveryRepositoryInterface;
use App\Services\CourierService\CourierServiceInterface;
use App\Services\OrderService\OrderServiceInterface;
use App\Services\OutboxService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeliveryService implements DeliveryServiceInterface
{
    private const MAX_ASSIGNMENT_ATTEMPTS = 3;

    public function __construct(
        private DeliveryRepositoryInterface $deliveries,
        private CourierRepositoryInterface $couriers,
    ) {}

    public function getDeliveryById(string $id): ?Delivery
    {
        return $this->deliveries->getById($id);
    }

    public function getDeliveryByOrderId(string $orderId): ?Delivery
    {
        return $this->deliveries->getByOrderId($orderId);
    }

    public function getActiveDeliveryByCourierId(string $courierId): ?Delivery
    {
        return $this->deliveries->getActiveByCourierId($courierId);
    }

    public function getDeliveriesByCourierId(string $courierId, ?array $statuses = null)
    {
        return $this->deliveries->getByCourierId($courierId, $statuses);
    }

    public function getDeliveryOffersByCourierId(string $courierId)
    {
        return $this->deliveries->getPendingOffersByCourierId($courierId);
    }

    #[Transactional]
    public function createDeliveryForOrder(string $orderId, float $deliveryFee): Delivery
    {
        $delivery = $this->deliveries->getOrCreateByOrderId($orderId, $deliveryFee);

        return $this->reloadDelivery($delivery);
    }

    #[Transactional]
    public function createDeliveryOfferForCourier(string $deliveryId, string $courierId, int $ttlSeconds = 30): DeliveryOffer
    {
        $offer = $this->deliveries->createOffer(new CreateDeliveryOfferDTO(
            deliveryId: $deliveryId,
            courierId: $courierId,
            status: DeliveryOfferStatus::PENDING,
            expiresAt: now()->addSeconds($ttlSeconds),
        ));

        $this->broadcastJobEvent(DeliveryOfferEventType::JOB_OFFERED, $offer);
        ExpireDeliveryOfferJob::dispatch($offer->id)
            ->delay($offer->expires_at)
            ->afterCommit();

        return $offer->load(['delivery.order', 'courier.user']);
    }

    public function assignCourierToDelivery(string $deliveryId): void
    {
        $delivery = $this->deliveries->getAssignmentCandidate($deliveryId);

        if (! $delivery || $delivery->courier_id !== null) {
            return;
        }

        if ($this->hasActivePendingOffer($delivery)) {
            return;
        }

        $attemptedCourierIds = $delivery->offers->pluck('courier_id')->all();
        $restaurantAddress = $delivery->order?->restaurant?->address;

        if (count($attemptedCourierIds) >= self::MAX_ASSIGNMENT_ATTEMPTS) {
            $this->failDeliveryWithoutCourier($delivery);

            return;
        }

        $maxRadiusKm = (float) config('services.delivery.assignment_radius_km', 10);

        $candidate = $this->couriers
            ->getAvailableExceptUserIds($attemptedCourierIds)
            ->map(function ($courier) use ($restaurantAddress, $maxRadiusKm): ?array {
                if (! $restaurantAddress || $courier->latitude === null || $courier->longitude === null) {
                    return [
                        'courier' => $courier,
                        'distance' => null,
                    ];
                }

                $distanceKm = GeoMath::distanceKm(
                    (float) $courier->latitude,
                    (float) $courier->longitude,
                    (float) $restaurantAddress->latitude,
                    (float) $restaurantAddress->longitude
                );

                if ($distanceKm > $maxRadiusKm) {
                    return null;
                }

                return [
                    'courier' => $courier,
                    'distance' => $distanceKm,
                ];
            })
            ->filter()
            ->sortBy(fn (array $candidate): float => $candidate['distance'] ?? PHP_FLOAT_MAX)
            ->first();

        $courier = $candidate['courier'] ?? null;

        if (! $courier) {
            return;
        }

        $this->createDeliveryOfferForCourier($delivery->id, $courier->user_id);
    }

    public function dispatchPendingCourierAssignments(): void
    {
        foreach ($this->deliveries->getPendingUnassignedDeliveryIds() as $deliveryId) {
            AssignCourierToDeliveryJob::dispatch($deliveryId)->afterCommit();
        }
    }

    #[Transactional]
    public function expireOfferByJob(string $offerId): void
    {
        $offer = $this->deliveries->getOfferById($offerId);

        if (! $offer || $offer->status !== DeliveryOfferStatus::PENDING || $offer->expires_at->isFuture()) {
            return;
        }

        $this->deliveries->updateOffer($offer, new UpdateDeliveryOfferDTO(status: DeliveryOfferStatus::EXPIRED));
        $this->broadcastJobEvent(DeliveryOfferEventType::JOB_EXPIRED, $offer);

        AssignCourierToDeliveryJob::dispatch($offer->delivery_id)->afterCommit();
    }

    #[Transactional]
    public function acceptDeliveryOffer(string $offerId): Delivery
    {
        $offer = $this->deliveries->getOfferByIdOrFail($offerId, lock: true);
        $offer->load(['delivery.order', 'courier']);

        if ($offer->status !== DeliveryOfferStatus::PENDING) {
            throw ValidationException::withMessages(['offer_id' => 'Offer is not pending.']);
        }

        if ($offer->expires_at->isPast()) {
            $this->deliveries->updateOffer($offer, new UpdateDeliveryOfferDTO(status: DeliveryOfferStatus::EXPIRED));
            $this->broadcastJobEvent(DeliveryOfferEventType::JOB_EXPIRED, $offer);

            throw ValidationException::withMessages(['offer_id' => 'Offer expired.']);
        }

        $delivery = $this->deliveries->getByIdOrFail($offer->delivery_id, lock: true);

        if ($delivery->courier_id !== null && $delivery->courier_id !== $offer->courier_id) {
            throw ValidationException::withMessages(['delivery_id' => 'Delivery already assigned.']);
        }

        if ($offer->courier->status !== CourierStatus::AVAILABLE) {
            throw ValidationException::withMessages(['courier_id' => 'Courier is not available.']);
        }

        $this->deliveries->updateOffer($offer, new UpdateDeliveryOfferDTO(
            status: DeliveryOfferStatus::ACCEPTED,
            acceptedAt: now(),
        ));

        $this->deliveries->updateDelivery($delivery, new UpdateDeliveryDTO(courierId: $offer->courier_id));
        $this->deliveries->expireOtherPendingOffers($delivery->id, $offer->id);

        app(CourierServiceInterface::class)->updateCourierStatus($offer->courier_id, CourierStatus::BUSY->value);
        $this->recordEvent($delivery, DeliveryEventType::DELIVERY_ACCEPTED);
        app(OrderServiceInterface::class)->recordCourierAssignedToOrder($delivery->order);
        $this->broadcastJobEvent(DeliveryOfferEventType::JOB_ACCEPTED, $offer);

        return $this->reloadDelivery($delivery);
    }

    #[Transactional]
    public function rejectDeliveryOffer(string $offerId): bool
    {
        $offer = $this->deliveries->getPendingOfferByIdOrFail($offerId);
        $offer->load('delivery');

        $this->deliveries->updateOffer($offer, new UpdateDeliveryOfferDTO(
            status: DeliveryOfferStatus::REJECTED,
            rejectedAt: now(),
        ));
        $this->broadcastJobEvent(DeliveryOfferEventType::JOB_REJECTED, $offer);
        AssignCourierToDeliveryJob::dispatch($offer->delivery_id)->afterCommit();

        return true;
    }

    #[Transactional]
    public function markDeliveryPickedUp(string $deliveryId, string $courierId): Delivery
    {
        $delivery = $this->transitionDeliveryStatus($deliveryId, $courierId, DeliveryStatus::PICKED_UP, ['pickup_time' => now()]);
        app(OrderServiceInterface::class)->recordOrderPickedUp($delivery->order);

        return $this->reloadDelivery($delivery);
    }

    #[Transactional]
    public function markDeliveryInTransit(string $deliveryId, string $courierId): Delivery
    {
        $delivery = $this->transitionDeliveryStatus($deliveryId, $courierId, DeliveryStatus::IN_TRANSIT);
        app(OrderServiceInterface::class)->markOrderOutForDelivery($delivery->order);

        return $this->reloadDelivery($delivery);
    }

    #[Transactional]
    public function markDeliveryDelivered(string $deliveryId, string $courierId): Delivery
    {
        $delivery = $this->transitionDeliveryStatus($deliveryId, $courierId, DeliveryStatus::DELIVERED, ['delivery_time' => now()]);
        app(OrderServiceInterface::class)->markOrderDelivered($delivery->order);
        app(CourierServiceInterface::class)->updateCourierStatus($courierId, CourierStatus::AVAILABLE->value);

        return $this->reloadDelivery($delivery);
    }

    #[Transactional]
    public function markDeliveryFailed(string $deliveryId, string $courierId, string $reason): Delivery
    {
        $delivery = $this->transitionDeliveryStatus($deliveryId, $courierId, DeliveryStatus::FAILED, ['failure_reason' => $reason]);

        return $delivery;
    }

    #[Transactional]
    public function markDeliveryFailedBySystem(string $deliveryId, string $reason): Delivery
    {
        $delivery = $this->deliveries->getByIdOrFail($deliveryId, lock: true);
        $delivery->load('order');

        if ($delivery->status === DeliveryStatus::FAILED || $delivery->status === DeliveryStatus::DELIVERED) {
            return $this->reloadDelivery($delivery);
        }

        DeliveryStateFactory::from($delivery->status)->transition($delivery, DeliveryStatus::FAILED);
        $this->recordEvent($delivery->refresh(), DeliveryEventType::DELIVERY_FAILED, [
            'reason' => $reason,
        ]);

        app(OrderServiceInterface::class)->cancelOrderBySystem($delivery->order_id, $reason);

        return $this->reloadDelivery($delivery);
    }

    private function transitionDeliveryStatus(string $deliveryId, string $courierId, DeliveryStatus $status, array $extra = []): Delivery
    {
        $delivery = $this->deliveries->getByIdAndCourierIdOrFail($deliveryId, $courierId, lock: true);
        DeliveryStateFactory::from($delivery->status)->transition($delivery, $status, $extra);
        $this->recordEvent($delivery, $this->eventTypeForStatus($status), $extra);

        return $this->reloadDelivery($delivery);
    }

    private function eventTypeForStatus(DeliveryStatus $status): DeliveryEventType
    {
        return match ($status) {
            DeliveryStatus::PICKED_UP => DeliveryEventType::DELIVERY_PICKED_UP,
            DeliveryStatus::IN_TRANSIT => DeliveryEventType::DELIVERY_IN_TRANSIT,
            DeliveryStatus::DELIVERED => DeliveryEventType::DELIVERY_DELIVERED,
            DeliveryStatus::FAILED => DeliveryEventType::DELIVERY_FAILED,
            DeliveryStatus::PENDING => DeliveryEventType::DELIVERY_ACCEPTED,
        };
    }

    private function reloadDelivery(Delivery $delivery): Delivery
    {
        return $this->deliveries->getById($delivery->id) ?? $delivery->refresh();
    }

    private function recordEvent(Delivery $delivery, DeliveryEventType $eventType, array $payload = []): void
    {
        $delivery->loadMissing('order');
        $occurredAt = now();
        $deliveryStatus = $delivery->status instanceof \BackedEnum
            ? $delivery->status->value
            : (string) $delivery->status;
        $eventPayload = [
            'eventId' => (string) Str::uuid(),
            'eventName' => $eventType->value,
            'aggregateType' => 'delivery',
            'aggregateId' => $delivery->id,
            'deliveryId' => $delivery->id,
            'orderId' => $delivery->order_id,
            'customerId' => $delivery->order?->user_id,
            'courierId' => $delivery->courier_id,
            'status' => $deliveryStatus,
            'deliveryStatus' => $deliveryStatus,
            'occurredAt' => $occurredAt->toIso8601String(),
            'data' => $payload,
            'channels' => array_values(array_filter([
                "order.{$delivery->order_id}.tracking",
                $delivery->courier_id ? "courier.{$delivery->courier_id}.jobs" : null,
            ])),
        ];

        $this->deliveries->createEvent($delivery, new CreateDeliveryEventDTO(
            eventType: $eventType,
            createdAt: $occurredAt,
            payload: $eventPayload,
        ));

        app(OutboxService::class)->enqueue('delivery', $delivery->id, $eventType->value, $eventPayload);
        NotificationEventRecorded::dispatch($eventType, $eventPayload);
    }

    private function failDeliveryWithoutCourier(Delivery $delivery): void
    {
        $this->markDeliveryFailedBySystem(
            $delivery->id,
            'NO_COURIER_AVAILABLE'
        );
    }

    private function hasActivePendingOffer(Delivery $delivery): bool
    {
        return $delivery->offers->contains(function (DeliveryOffer $offer): bool {
            return $offer->status === DeliveryOfferStatus::PENDING
                && $offer->expires_at?->isFuture();
        });
    }

    private function broadcastJobEvent(DeliveryOfferEventType $eventType, DeliveryOffer $offer): void
    {
        $offer->loadMissing([
            'delivery.order.user',
            'delivery.order.address',
            'delivery.order.items.options',
            'delivery.order.restaurant.address',
            'courier.user',
        ]);

        $offerSnapshot = $this->offerSnapshot($offer);

        $payload = [
            'eventId' => (string) Str::uuid(),
            'eventName' => $eventType->value,
            'aggregateType' => 'delivery_offer',
            'aggregateId' => $offer->id,
            'offerId' => $offer->id,
            'offer_id' => $offer->id,
            'deliveryId' => $offer->delivery_id,
            'delivery_id' => $offer->delivery_id,
            'orderId' => $offerSnapshot['order_id'] ?? null,
            'order_id' => $offerSnapshot['order_id'] ?? null,
            'courierId' => $offer->courier_id,
            'courier_id' => $offer->courier_id,
            'expiresAt' => $offer->expires_at?->toIso8601String(),
            'expires_at' => $offer->expires_at?->toIso8601String(),
            'status' => $offer->status->value,
            'offer' => $offerSnapshot,
            'delivery' => $offerSnapshot['delivery'] ?? null,
            'order' => $offerSnapshot['order'] ?? null,
            'customerName' => $offerSnapshot['customer_name'] ?? null,
            'restaurantName' => $offerSnapshot['restaurant_name'] ?? null,
            'pickupAddress' => $offerSnapshot['pickup_address'] ?? null,
            'dropoffAddress' => $offerSnapshot['dropoff_address'] ?? null,
            'items' => $offerSnapshot['items'] ?? [],
            'occurredAt' => now()->toIso8601String(),
            'data' => [
                'offer_id' => $offer->id,
                'delivery_id' => $offer->delivery_id,
                'order_id' => $offerSnapshot['order_id'] ?? null,
            ],
            'channels' => ["courier.{$offer->courier_id}.jobs"],
        ];

        app(OutboxService::class)->enqueue('delivery_offer', $offer->id, $eventType->value, $payload);
        NotificationEventRecorded::dispatch($eventType, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function offerSnapshot(DeliveryOffer $offer): array
    {
        $delivery = $offer->delivery;
        $order = $delivery?->order;
        $pickupAddress = $order?->restaurant?->address;
        $dropoffAddress = $order?->address;

        $items = $order?->items
            ? $order->items->map(fn ($item): array => [
                'id' => $item->id,
                'status' => $this->enumValue($item->status),
                'quantity' => (int) $item->quantity,
                'product_name' => $item->product_name_snapshot,
                'product_name_snapshot' => $item->product_name_snapshot,
                'options' => $item->options->map(fn ($option): array => [
                    'id' => $option->id,
                    'option_name' => $option->option_name_snapshot,
                    'option_name_snapshot' => $option->option_name_snapshot,
                    'extra_price' => (float) $option->extra_price,
                ])->values()->all(),
            ])->values()->all()
            : [];

        return [
            'id' => $offer->id,
            'offer_token' => $offer->id,
            'delivery_id' => $offer->delivery_id,
            'order_id' => $delivery?->order_id,
            'courier_id' => $offer->courier_id,
            'status' => $this->enumValue($offer->status),
            'expires_at' => $offer->expires_at?->toIso8601String(),
            'restaurant_name' => $order?->restaurant_name_snapshot,
            'customer_name' => $order?->user?->name,
            'customer_id' => $order?->user_id,
            'order_total' => $order?->total !== null ? (float) $order->total : null,
            'pickup_address' => $this->addressLine($pickupAddress),
            'dropoff_address' => $this->addressLine($dropoffAddress),
            'items' => $items,
            'delivery' => $delivery ? [
                'id' => $delivery->id,
                'order_id' => $delivery->order_id,
                'courier_id' => $delivery->courier_id,
                'status' => $this->enumValue($delivery->status),
                'delivery_fee' => (float) $delivery->delivery_fee,
            ] : null,
            'order' => $order ? [
                'id' => $order->id,
                'user_id' => $order->user_id,
                'status' => $this->enumValue($order->status),
                'total' => (float) $order->total,
                'restaurant_name_snapshot' => $order->restaurant_name_snapshot,
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ] : null,
                'address' => $this->addressSnapshot($dropoffAddress),
                'restaurant' => [
                    'name' => $order->restaurant_name_snapshot,
                    'address' => $this->addressSnapshot($pickupAddress),
                ],
                'items' => $items,
            ] : null,
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    private function addressLine(?object $address): ?string
    {
        if (! $address) {
            return null;
        }

        return collect([
            $address->street ?? null,
            $address->city ?? null,
            $address->postal_code ?? null,
        ])->filter()->implode(', ') ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function addressSnapshot(?object $address): ?array
    {
        if (! $address) {
            return null;
        }

        return [
            'street' => $address->street ?? null,
            'city' => $address->city ?? null,
            'postal_code' => $address->postal_code ?? null,
            'country' => $address->country ?? null,
            'latitude' => $address->latitude ?? null,
            'longitude' => $address->longitude ?? null,
        ];
    }
}
