<?php

namespace App\Outbox\EventHandlers;

use App\Events\OutboxEventPublished;
use App\Events\RestaurantRatingThresholdReached;

class RestaurantRatingThresholdOutboxEventHandler implements OutboxEventHandler
{
    public function handle(OutboxEventPublished $event): void
    {
        event(new RestaurantRatingThresholdReached($event->payload));
    }
}
