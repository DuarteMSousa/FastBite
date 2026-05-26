<?php

namespace App\Outbox\EventHandlers;

use App\Events\OrderStatusUpdated;
use App\Events\OutboxEventPublished;

class OrderStatusUpdatedOutboxEventHandler implements OutboxEventHandler
{
    public function handle(OutboxEventPublished $event): void
    {
        event(new OrderStatusUpdated($event->payload));
    }
}
