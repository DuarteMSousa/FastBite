<?php

namespace App\Gateway\ServerEvents\Handlers;

use App\Enums\OutboxEventName;
use App\Events\UserNotificationCreated;
use App\Gateway\GatewayClientSocketPusher;
use App\Gateway\ServerEvents\SocketEventHandler;

class UserNotificationCreatedSocketHandler implements SocketEventHandler
{
    public function __construct(private GatewayClientSocketPusher $pusher) {}

    public function supports(object $event): bool
    {
        return $event instanceof UserNotificationCreated;
    }

    public function handle(object $event): void
    {
        /** @var UserNotificationCreated $event */
        $userId = $event->payload['userId'] ?? $event->payload['user_id'] ?? null;

        if (! $userId) {
            return;
        }

        $this->pusher->sendToGroup(
            "user.{$userId}.notifications",
            OutboxEventName::USER_NOTIFICATION_CREATED->value,
            $event->payload
        );
    }
}
