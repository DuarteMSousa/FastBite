<?php

namespace App\Gateway\ServerEvents\Handlers;

use App\Aspects\ErrorLogged;
use App\Aspects\Logged;
use App\Enums\OutboxEventType;
use App\Events\OrderStatusUpdated;
use App\Gateway\GatewayClientSocketPusher;
use App\Gateway\ServerEvents\SocketEventHandler;

class OrderStatusUpdatedSocketHandler implements SocketEventHandler
{
    public function __construct(private GatewayClientSocketPusher $pusher) {}

    public function supports(object $event): bool
    {
        return $event instanceof OrderStatusUpdated;
    }

    #[Logged]
    #[ErrorLogged]
    public function handle(object $event): void
    {
        /** @var OrderStatusUpdated $event */
        $orderId = $event->payload['orderId'] ?? $event->payload['order_id'] ?? null;
        $customerId = $event->payload['customerId'] ?? $event->payload['customer_id'] ?? null;
        $restaurantId = $event->payload['restaurantId'] ?? $event->payload['restaurant_id'] ?? null;

        if ($orderId) {
            $this->pusher->sendToGroup(
                "order.{$orderId}.tracking",
                OutboxEventType::ORDER_STATUS_UPDATED->value,
                $event->payload
            );
        }

        if ($customerId) {
            $this->pusher->sendToGroup(
                "customer.{$customerId}.orders",
                OutboxEventType::ORDER_STATUS_UPDATED->value,
                $event->payload
            );
        }

        if ($restaurantId) {
            $this->pusher->sendToGroup(
                "restaurant.{$restaurantId}.orders",
                OutboxEventType::ORDER_STATUS_UPDATED->value,
                $event->payload
            );
        }
    }
}
