<?php

namespace App\Gateway\ServerEvents\Handlers;

use App\Enums\OutboxEventType;
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
            OutboxEventType::USER_NOTIFICATION_CREATED->value,
            $event->payload
        );
    }
}
