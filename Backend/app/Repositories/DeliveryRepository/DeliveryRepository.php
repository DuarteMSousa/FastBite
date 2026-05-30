<?php

namespace App\Repositories\DeliveryRepository;

use App\DTOs\Delivery\CreateDeliveryEventDTO;
use App\DTOs\Delivery\UpdateDeliveryDTO;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Delivery;

class DeliveryRepository implements DeliveryRepositoryInterface
{
    private array $deliveryDetails = [
        'order.user',
        'order.address',
        'order.items.options',
        'order.restaurant.address',
        'courier.user',
        'positionHistory',
        'events',
        'offers',
    ];

    public function getById(string $id): ?Delivery
    {
        return Delivery::query()->with($this->deliveryDetails)->find($id);
    }

    public function getByCourierId(string $courierId, ?array $statuses)
    {
        $query = Delivery::query()->with($this->deliveryDetails)->where('courier_id', $courierId);

        if ($statuses) {
            $query->whereIn('status', $statuses);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function getAssignmentCandidate(string $id): ?Delivery
    {
        return Delivery::query()
            ->with(['order.restaurant.address', 'offers'])
            ->find($id);
    }

    public function getPendingUnassignedDeliveryIds(int $limit = 25): array
    {
        return Delivery::query()
            ->whereNull('courier_id')
            ->where('status', DeliveryStatus::PENDING->value)
            ->whereHas('order', fn ($query) => $query->whereIn('status', [
                OrderStatus::PENDING->value,
                OrderStatus::COURIER_ASSIGNED->value,
                OrderStatus::CONFIRMED->value,
            ]))
            ->orderBy('created_at')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    public function getByIdOrFail(string $id, bool $lock = false): Delivery
    {
        $query = Delivery::query();

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    public function getByIdAndCourierIdOrFail(string $id, string $courierId, bool $lock = false): Delivery
    {
        $query = Delivery::query()->where('courier_id', $courierId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    public function getOrCreateByOrderId(string $orderId, float $deliveryFee): Delivery
    {
        return Delivery::query()->firstOrCreate(
            ['order_id' => $orderId],
            ['status' => DeliveryStatus::PENDING->value, 'delivery_fee' => $deliveryFee]
        );
    }

    public function createEvent(Delivery $delivery, CreateDeliveryEventDTO $data): void
    {
        $delivery->events()->create([
            'event_type' => $data->eventType->value,
            'payload' => $data->payload,
            'created_at' => $data->createdAt,
        ]);
    }

    public function updateDelivery(Delivery $delivery, UpdateDeliveryDTO $data): Delivery
    {
        $delivery->update(array_filter([
            'courier_id' => $data->courierId,
            'status' => $data->status?->value,
            'pickup_time' => $data->pickupTime,
            'delivery_time' => $data->deliveryTime,
        ], static fn ($value) => $value !== null));

        return $delivery;
    }

}
