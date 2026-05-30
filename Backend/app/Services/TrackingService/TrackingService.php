<?php

namespace App\Services\TrackingService;

use App\Aspects\Transactional;
use App\DTOs\Tracking\UpdateCourierLocationDTO;
use App\Enums\DeliveryStatus;
use App\Enums\OutboxAggregateType;
use App\Enums\OutboxEventType;
use App\Models\CourierPositionHistory;
use App\Models\Delivery;
use App\Repositories\TrackingRepository\TrackingRepositoryInterface;
use App\Services\CourierService\CourierServiceInterface;
use App\Services\OutboxService;
use App\Services\RoutingService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrackingService implements TrackingServiceInterface
{
    private TrackingRepositoryInterface $trackingRepository;
    private RoutingService $routing;

    public function __construct(?TrackingRepositoryInterface $trackingRepository = null, ?RoutingService $routing = null)
    {
        $this->trackingRepository = $trackingRepository ?? app(TrackingRepositoryInterface::class);
        $this->routing = $routing ?? app(RoutingService::class);
    }

    public function orderTracking(string $userId, string $orderId): array
    {
        $order = $this->trackingRepository->findOrderForUserTracking($userId, $orderId);

        if (! $order) {
            throw ValidationException::withMessages([
                'order_id' => 'Not authorized to access this order tracking.',
            ]);
        }

        $delivery = $order->delivery;
        $lastPosition = $delivery ? $this->trackingRepository->findLastPositionForDelivery($delivery->id) : null;

        return [
            'order' => $order,
            'delivery' => $delivery,
            'courier' => $delivery?->courier,
            'last_position' => $lastPosition,
            ...$this->trackingRepositoryRoute($delivery, $lastPosition),
        ];
    }

    public function deliveryTracking(string $deliveryId): array
    {
        $delivery = $this->trackingRepository->findDeliveryForTracking($deliveryId);
        $lastPosition = $this->trackingRepository->findLastPositionForDelivery($delivery->id);

        return [
            'delivery' => $delivery,
            'last_position' => $lastPosition,
            ...$this->trackingRepositoryRoute($delivery, $lastPosition),
        ];
    }

    #[Transactional]
    public function updateCourierLocation(UpdateCourierLocationDTO $data): array
    {
        if ($data->latitude < -90 || $data->latitude > 90 || $data->longitude < -180 || $data->longitude > 180) {
            throw ValidationException::withMessages([
                'coordinates' => 'Latitude or longitude are outside valid ranges.',
            ]);
        }

        $delivery = $this->trackingRepository->findDeliveryForCourierOrFail($data->courier_id, $data->delivery_id);

        app(CourierServiceInterface::class)->updateCourierLocation(
            $data->courier_id,
            $data->latitude,
            $data->longitude
        );

        $timestamp = $data->recorded_at ?? now()->toIso8601String();
        $this->trackingRepository->createPosition($delivery->id, $data->latitude, $data->longitude, $timestamp);
        $route = $this->trackingRepositoryRoute($delivery, null, $data->latitude, $data->longitude);

        app(OutboxService::class)->enqueue(OutboxAggregateType::DELIVERY, $delivery->id, OutboxEventType::COURIER_POSITION_UPDATED, [
            'eventId' => (string) Str::uuid(),
            'eventName' => OutboxEventType::COURIER_POSITION_UPDATED->value,
            'orderId' => $delivery->order_id,
            'deliveryId' => $delivery->id,
            'courierId' => $data->courier_id,
            'lat' => $data->latitude,
            'lng' => $data->longitude,
            'recordedAt' => $timestamp,
            'routePoints' => $route['route_points'],
            'routeDistanceKm' => $route['route_distance_km'],
            'routeDurationSeconds' => $route['route_duration_seconds'],
            'distanceKmRemaining' => $route['distance_km_remaining'],
            'etaSeconds' => $route['eta_seconds'],
            'routeProvider' => $route['route_provider'],
        ]);

        return [
            'ok' => true,
            'delivery_id' => $delivery->id,
            'recorded_at' => $timestamp,
        ];
    }

    /**
     * @return array{
     *     route_points: array<int, array{lat: float, lng: float}>,
     *     route_distance_km: float|null,
     *     route_duration_seconds: int|null,
     *     distance_km_remaining: float|null,
     *     eta_seconds: int|null,
     *     route_provider: string
     * }
     */
    private function trackingRoute(
        ?Delivery $delivery,
        ?CourierPositionHistory $lastPosition = null,
        ?float $originLat = null,
        ?float $originLng = null
    ): array {
        if (! $delivery) {
            return $this->emptyRoute();
        }

        $delivery->loadMissing(['courier', 'order.address', 'order.restaurant.address']);

        if ($this->isTerminalDelivery($delivery)) {
            return $this->emptyRoute();
        }

        $originLat ??= $lastPosition?->latitude ?? $delivery->courier?->latitude;
        $originLng ??= $lastPosition?->longitude ?? $delivery->courier?->longitude;
        [$destinationLat, $destinationLng] = $this->destinationForDelivery($delivery);

        $route = $this->routing->routeBetween($originLat, $originLng, $destinationLat, $destinationLng);

        return [
            'route_points' => $route['points'],
            'route_distance_km' => $route['distance_km'],
            'route_duration_seconds' => $route['duration_seconds'],
            'distance_km_remaining' => $route['distance_km'],
            'eta_seconds' => $route['duration_seconds'],
            'route_provider' => $route['provider'],
        ];
    }

    /**
     * @return array{
     *     route_points: array<int, array{lat: float, lng: float}>,
     *     route_distance_km: float|null,
     *     route_duration_seconds: int|null,
     *     distance_km_remaining: float|null,
     *     eta_seconds: int|null,
     *     route_provider: string
     * }
     */
    private function emptyRoute(): array
    {
        return [
            'route_points' => [],
            'route_distance_km' => null,
            'route_duration_seconds' => null,
            'distance_km_remaining' => null,
            'eta_seconds' => null,
            'route_provider' => 'none',
        ];
    }

    /**
     * @return array{0: float|null, 1: float|null}
     */
    private function destinationForDelivery(Delivery $delivery): array
    {
        if (in_array($this->deliveryStatus($delivery), [DeliveryStatus::PICKED_UP, DeliveryStatus::IN_TRANSIT], true)) {
            return [
                $delivery->order?->address?->latitude,
                $delivery->order?->address?->longitude,
            ];
        }

        return [
            $delivery->order?->restaurant?->address?->latitude,
            $delivery->order?->restaurant?->address?->longitude,
        ];
    }

    private function isTerminalDelivery(Delivery $delivery): bool
    {
        return in_array($this->deliveryStatus($delivery), [DeliveryStatus::DELIVERED, DeliveryStatus::FAILED], true);
    }

    private function deliveryStatus(Delivery $delivery): ?DeliveryStatus
    {
        if ($delivery->status instanceof DeliveryStatus) {
            return $delivery->status;
        }

        return DeliveryStatus::tryFrom((string) $delivery->status);
    }
}
