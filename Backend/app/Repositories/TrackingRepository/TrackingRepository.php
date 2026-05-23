<?php

namespace App\Repositories\TrackingRepository;

use App\Models\CourierPositionHistory;
use App\Models\Delivery;
use App\Models\Order;

class TrackingRepository implements TrackingRepositoryInterface
{
    public function findOrderForUserTracking(string $userId, string $orderId)
    {
        return Order::with(['address', 'restaurant.address', 'delivery.courier', 'delivery.positionHistory'])
            ->where('user_id', $userId)
            ->find($orderId);
    }

    public function findDeliveryForTracking(string $deliveryId)
    {
        return Delivery::with(['courier', 'positionHistory', 'order.address', 'order.restaurant.address'])->findOrFail($deliveryId);
    }

    public function findDeliveryForCourierOrFail(string $courierId, string $deliveryId)
    {
        return Delivery::with(['courier', 'order.address', 'order.restaurant.address'])
            ->where('courier_id', $courierId)
            ->findOrFail($deliveryId);
    }

    public function findLastPositionForDelivery(string $deliveryId)
    {
        return CourierPositionHistory::where('delivery_id', $deliveryId)
            ->orderByDesc('timestamp')
            ->first();
    }

    public function findLastPositionForCourier(string $courierId)
    {
        return CourierPositionHistory::whereHas('delivery', fn ($query) => $query->where('courier_id', $courierId))
            ->orderByDesc('timestamp')
            ->first();
    }

    public function createPosition(string $deliveryId, float $latitude, float $longitude, string $timestamp): void
    {
        CourierPositionHistory::create([
            'delivery_id' => $deliveryId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timestamp' => $timestamp,
        ]);
    }
}
