<?php

namespace App\Outbox\Handlers;

use App\Enums\OutboxEventType;
use App\Events\OutboxEventPublished;
use App\Services\NotificationService\NotificationServiceInterface;

class CreateNotificationFromOutboxEventHandler
{
    public function __construct(private NotificationServiceInterface $notifications)
    {
    }

    public function handle(OutboxEventPublished $event): void
    {
        $eventType = OutboxEventType::tryFrom($event->eventName);

        if (! $eventType || $eventType === OutboxEventType::USER_NOTIFICATION_CREATED) {
            return;
        }

        $this->notifications->createFromEvent($eventType, $event->payload);
    }
}
