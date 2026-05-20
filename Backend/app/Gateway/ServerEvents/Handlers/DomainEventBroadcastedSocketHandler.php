<?php

namespace App\Gateway\ServerEvents\Handlers;

use App\Events\DomainEventBroadcasted;
use App\Gateway\GatewayClientSocketPusher;
use App\Gateway\ServerEvents\SocketEventHandler;

class DomainEventBroadcastedSocketHandler implements SocketEventHandler
{
    public function __construct(private GatewayClientSocketPusher $pusher) {}

    public function supports(object $event): bool
    {
        return $event instanceof DomainEventBroadcasted;
    }

    public function handle(object $event): void
    {
        /** @var DomainEventBroadcasted $event */
        foreach ($event->channels as $channel) {
            $this->pusher->sendToGroup($channel, $event->eventName, $event->payload);
        }
    }
}
