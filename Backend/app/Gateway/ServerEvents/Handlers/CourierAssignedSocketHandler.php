<?php

namespace App\Gateway\ServerEvents\Handlers;

use App\Enums\OutboxEventType;
use App\Events\CourierAssigned;
use App\Gateway\GatewayClientSocketPusher;
use App\Gateway\ServerEvents\SocketEventHandler;

class CourierAssignedSocketHandler implements SocketEventHandler
{
    public function __construct(private GatewayClientSocketPusher $pusher) {}

    public function supports(object $event): bool
    {
        return $event instanceof CourierAssigned;
    }

    public function handle(object $event): void
    {
        /** @var CourierAssigned $event */
        $orderId = $event->payload['orderId'] ?? $event->payload['order_id'] ?? null;
        $courierId = $event->payload['courierId']
            ?? $event->payload['courier_id']
            ?? $event->payload['order']['delivery']['courier_id']
            ?? null;

        if ($orderId) {
            $this->pusher->sendToGroup(
                "order.{$orderId}.tracking",
                OutboxEventType::ORDER_COURIER_ASSIGNED->value,
                $event->payload
            );
        }

        if ($courierId) {
            $this->pusher->sendToGroup(
                "courier.{$courierId}.jobs",
                OutboxEventType::ORDER_COURIER_ASSIGNED->value,
                $event->payload
            );
        }
    }
}
