<?php

namespace App\Gateway\ServerEvents\Handlers;

use App\Enums\OutboxEventType;
use App\Events\CourierPositionUpdated;
use App\Gateway\GatewayClientSocketPusher;
use App\Gateway\ServerEvents\SocketEventHandler;

class CourierPositionUpdatedSocketHandler implements SocketEventHandler
{
    public function __construct(private GatewayClientSocketPusher $pusher) {}

    public function supports(object $event): bool
    {
        return $event instanceof CourierPositionUpdated;
    }

    public function handle(object $event): void
    {
        /** @var CourierPositionUpdated $event */
        $orderId = $event->payload['orderId'] ?? $event->payload['order_id'] ?? null;

        if (! $orderId) {
            return;
        }

        $this->pusher->sendToGroup(
            "order.{$orderId}.tracking",
            OutboxEventType::COURIER_POSITION_UPDATED->value,
            $event->payload
        );
    }
}
