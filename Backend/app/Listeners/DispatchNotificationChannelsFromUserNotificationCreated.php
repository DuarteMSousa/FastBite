<?php

namespace App\Listeners;

use App\Events\UserNotificationCreated;
use App\Jobs\DispatchNotificationChannelsJob;

class DispatchNotificationChannelsFromUserNotificationCreated
{
    public function handle(UserNotificationCreated $event): void
    {
        if (! isset($event->payload['notificationId'])) {
            return;
        }

        DispatchNotificationChannelsJob::dispatch(
            notificationId: (string) $event->payload['notificationId'],
            payload: $event->payload
        );
    }
}
