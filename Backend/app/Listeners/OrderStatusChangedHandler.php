<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Enums\OutboxAggregateType;
use App\Enums\OutboxEventType;
use App\Models\Order;
use App\Repositories\OrderRepository\OrderRepositoryInterface;
use App\Services\OutboxService;
use Illuminate\Support\Str;

class OrderStatusChangedHandler
{
    public function __invoke(Order $order, OrderStatus $previousStatus, OrderStatus $newStatus): void
    {
        $order->refresh()->loadMissing([
            'user',
            'address',
            'payment',
            'delivery',
            'events',
            'items',
        ]);

        $eventType = OutboxEventType::from($newStatus->toEventType()->value);
        $occurredAt = now();

        $eventPayload = [
            'eventId' => (string) Str::uuid(),
            'eventName' => $eventType->value,
            'aggregateType' => 'order',
            'aggregateId' => $order->id,
            'orderId' => $order->id,
            'customerId' => $order->user_id,
            'restaurantId' => $order->restaurant_id,
            'restaurantName' => $order->restaurant_name_snapshot,
            'deliveryId' => $order->delivery?->id,
            'courierId' => $order->delivery?->courier_id,
            'previousStatus' => $previousStatus->value,
            'newStatus' => $newStatus->value,
            'status' => $newStatus->value,
            'occurredAt' => $occurredAt->toIso8601String(),
            'order' => $this->orderSnapshot($order),
            'data' => [],
        ];

        app(OrderRepositoryInterface::class)->addEvent(
            $order, $eventType->value, $occurredAt, $eventPayload
        );

        app(OutboxService::class)->enqueue(
            OutboxAggregateType::ORDER,
            $order->id,
            $eventType,
            $eventPayload
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function orderSnapshot(Order $order): array
    {
        return [
            'id' => $order->id,
            'user_id' => $order->user_id,
            'restaurant_id' => $order->restaurant_id,
            'status' => $order->status->value,
            'total' => $order->total,
            'restaurant_name_snapshot' => $order->restaurant_name_snapshot,
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
            'user' => $order->user ? [
                'id' => $order->user->id,
                'name' => $order->user->name,
                'email' => $order->user->email,
            ] : null,
            'address' => $order->address ? [
                'street' => $order->address->street,
                'city' => $order->address->city,
                'postal_code' => $order->address->postal_code,
                'country' => $order->address->country,
                'latitude' => $order->address->latitude,
                'longitude' => $order->address->longitude,
            ] : null,
            'payment' => $order->payment ? [
                'id' => $order->payment->id,
                'method' => $order->payment->method->value,
                'status' => $order->payment->status->value,
            ] : null,
            'delivery' => $order->delivery ? [
                'id' => $order->delivery->id,
                'courier_id' => $order->delivery->courier_id,
                'status' => $order->delivery->status->value,
            ] : null,
            'events' => $order->events->map(fn ($event): array => [
                'event_type' => $event->event_type,
                'timestamp' => $event->timestamp?->toIso8601String(),
            ])->all(),
            'items' => $order->items->map(fn ($item): array => [
                'id' => $item->id,
                'status' => $item->status->value,
                'quantity' => $item->quantity,
                'product_name_snapshot' => $item->product_name_snapshot,
                'total_price' => $item->total_price,
            ])->all(),
        ];
    }
}
