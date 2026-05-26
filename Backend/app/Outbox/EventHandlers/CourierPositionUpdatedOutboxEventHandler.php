<?php

namespace App\Outbox\EventHandlers;

use App\Events\CourierPositionUpdated;
use App\Events\OutboxEventPublished;

class CourierPositionUpdatedOutboxEventHandler implements OutboxEventHandler
{
    public function handle(OutboxEventPublished $event): void
    {
        event(new CourierPositionUpdated($event->payload));
    }
}
