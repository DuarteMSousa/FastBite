<?php

namespace App\Outbox\EventHandlers;

use App\Events\OutboxEventPublished;
use App\Events\UserOrderMilestoneReached;

class UserOrderMilestoneOutboxEventHandler implements OutboxEventHandler
{
    public function handle(OutboxEventPublished $event): void
    {
        event(new UserOrderMilestoneReached($event->payload));
    }
}
