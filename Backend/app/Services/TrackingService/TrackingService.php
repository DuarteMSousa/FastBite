<?php

namespace App\Services\TrackingService;

use App\Aspects\Transactional;
use App\DTOs\Tracking\UpdateCourierLocationDTO;
use App\Enums\OutboxEventName;
use App\Models\CourierPositionHistory;
use App\Repositories\TrackingRepository\TrackingRepositoryInterface;
use App\Services\CourierService\CourierServiceInterface;
use App\Services\OutboxService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrackingService implements TrackingServiceInterface
{
    private TrackingRepositoryInterface $tracking;

    public function __construct(?TrackingRepositoryInterface $tracking = null)
    {
        $this->tracking = $tracking ?? app(TrackingRepositoryInterface::class);
    }

    public function orderTracking(string $userId, string $orderId): array
    {
        $order = $this->tracking->findOrderForUserTracking($userId, $orderId);

        if (! $order) {
            throw ValidationException::withMessages([
                'order_id' => 'Not authorized to access this order tracking.',
            ]);
        }

        $delivery = $order->delivery;

        return [
            'order' => $order,
            'delivery' => $delivery,
            'courier' => $delivery?->courier,
            'last_position' => $delivery ? $this->tracking->findLastPositionForDelivery($delivery->id) : null,
            'eta_seconds' => null,
        ];
    }

    public function deliveryTracking(string $deliveryId): array
    {
        $delivery = $this->tracking->findDeliveryForTracking($deliveryId);

        return [
            'delivery' => $delivery,
            'last_position' => $this->tracking->findLastPositionForDelivery($delivery->id),
            'eta_seconds' => null,
        ];
    }

    public function courierLastPosition(string $courierId): ?CourierPositionHistory
    {
        return $this->tracking->findLastPositionForCourier($courierId);
    }

    #[Transactional]
    public function updateCourierLocation(UpdateCourierLocationDTO $data): array
    {
        if ($data->latitude < -90 || $data->latitude > 90 || $data->longitude < -180 || $data->longitude > 180) {
            throw ValidationException::withMessages([
                'coordinates' => 'Latitude or longitude are outside valid ranges.',
            ]);
        }

        $delivery = $this->tracking->findDeliveryForCourierOrFail($data->courier_id, $data->delivery_id);

        app(CourierServiceInterface::class)->updateCourierLocation(
            $data->courier_id,
            $data->latitude,
            $data->longitude
        );

        $timestamp = $data->recorded_at ?? now()->toIso8601String();
        $this->tracking->createPosition($delivery->id, $data->latitude, $data->longitude, $timestamp);

        app(OutboxService::class)->enqueue('delivery', $delivery->id, OutboxEventName::COURIER_POSITION_UPDATED->value, [
            'eventId' => (string) Str::uuid(),
            'eventName' => OutboxEventName::COURIER_POSITION_UPDATED->value,
            'orderId' => $delivery->order_id,
            'deliveryId' => $delivery->id,
            'courierId' => $data->courier_id,
            'lat' => $data->latitude,
            'lng' => $data->longitude,
            'heading' => $data->heading,
            'speed' => $data->speed,
            'accuracy' => $data->accuracy,
            'recordedAt' => $timestamp,
            'etaSeconds' => null,
        ]);

        return [
            'ok' => true,
            'delivery_id' => $delivery->id,
            'recorded_at' => $timestamp,
        ];
    }
}
