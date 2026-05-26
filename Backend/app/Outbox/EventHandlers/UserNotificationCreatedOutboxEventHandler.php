<?php

namespace App\Outbox\EventHandlers;

use App\Events\OutboxEventPublished;
use App\Events\UserNotificationCreated;

class UserNotificationCreatedOutboxEventHandler implements OutboxEventHandler
{
    public function handle(OutboxEventPublished $event): void
    {
        event(new UserNotificationCreated($event->payload));
    }
}
