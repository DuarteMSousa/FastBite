<?php

namespace App\Gateway\ServerEvents\Handlers;

use App\Enums\OutboxEventType;
use App\Events\DeliveryOffered;
use App\Gateway\GatewayClientSocketPusher;
use App\Gateway\ServerEvents\SocketEventHandler;

class DeliveryOfferedSocketHandler implements SocketEventHandler
{
    public function __construct(private GatewayClientSocketPusher $pusher) {}

    public function supports(object $event): bool
    {
        return $event instanceof DeliveryOffered;
    }

    public function handle(object $event): void
    {
        /** @var DeliveryOffered $event */
        $courierId = $event->payload['courierId']
            ?? $event->payload['courier_id']
            ?? $event->payload['offer']['courier_id']
            ?? null;

        if (! $courierId) {
            return;
        }

        $this->pusher->sendToGroup(
            "courier.{$courierId}.jobs",
            OutboxEventType::JOB_OFFERED->value,
            $event->payload
        );
    }
}
