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
            'previousStatus' => $previousStatus->value,
            'newStatus' => $newStatus->value,
            'occurredAt' => $occurredAt->toIso8601String(),
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
}
