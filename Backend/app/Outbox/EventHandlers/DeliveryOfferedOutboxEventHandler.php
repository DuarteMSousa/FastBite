<?php

namespace App\Outbox\EventHandlers;

use App\Events\DeliveryOffered;
use App\Events\OutboxEventPublished;

class DeliveryOfferedOutboxEventHandler implements OutboxEventHandler
{
    public function handle(OutboxEventPublished $event): void
    {
        event(new DeliveryOffered($event->payload));
    }
}
