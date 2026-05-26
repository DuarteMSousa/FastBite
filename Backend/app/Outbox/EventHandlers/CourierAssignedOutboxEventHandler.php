<?php

namespace App\Outbox\EventHandlers;

use App\Events\CourierAssigned;
use App\Events\OrderStatusUpdated;
use App\Events\OutboxEventPublished;

class CourierAssignedOutboxEventHandler implements OutboxEventHandler
{
    public function handle(OutboxEventPublished $event): void
    {
        event(new CourierAssigned($event->payload));
        event(new OrderStatusUpdated($event->payload));
    }
}
