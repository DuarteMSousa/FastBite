<?php

namespace App\Repositories\TrackingRepository;

use App\Models\CourierPositionHistory;
use App\Models\Delivery;
use App\Models\Order;

class TrackingRepository implements TrackingRepositoryInterface
{
    public function findOrderForUserTracking(string $userId, string $orderId)
    {
        return Order::with([
            'address',
            'events',
            'items.options',
            'restaurant.address',
            'user',
            'delivery.courier.user',
            'delivery.events',
            'delivery.positionHistory',
        ])
            ->where('user_id', $userId)
            ->find($orderId);
    }

    public function findDeliveryForTracking(string $deliveryId)
    {
        return Delivery::with([
            'courier.user',
            'events',
            'positionHistory',
            'order.address',
            'order.events',
            'order.items.options',
            'order.restaurant.address',
            'order.user',
        ])->findOrFail($deliveryId);
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
