<?php

namespace App\Listeners;

use App\Enums\OutboxEventType;
use App\Events\OutboxEventPublished;
use App\Outbox\EventHandlers\ChatMessageSentOutboxEventHandler;
use App\Outbox\EventHandlers\CourierAssignedOutboxEventHandler;
use App\Outbox\EventHandlers\CourierPositionUpdatedOutboxEventHandler;
use App\Outbox\EventHandlers\DeliveryOfferedOutboxEventHandler;
use App\Outbox\EventHandlers\OrderStatusUpdatedOutboxEventHandler;
use App\Outbox\EventHandlers\OutboxEventHandler;
use App\Outbox\EventHandlers\UserNotificationCreatedOutboxEventHandler;

class DispatchLaravelEventFromOutbox
{
    /**
     * @var array<string, class-string<OutboxEventHandler>>
     */
    private array $handlers = [
        OutboxEventType::CHAT_MESSAGE_SENT->value => ChatMessageSentOutboxEventHandler::class,
        OutboxEventType::COURIER_POSITION_UPDATED->value => CourierPositionUpdatedOutboxEventHandler::class,
        OutboxEventType::USER_NOTIFICATION_CREATED->value => UserNotificationCreatedOutboxEventHandler::class,
        OutboxEventType::JOB_OFFERED->value => DeliveryOfferedOutboxEventHandler::class,
        OutboxEventType::ORDER_COURIER_ASSIGNED->value => CourierAssignedOutboxEventHandler::class,
        OutboxEventType::ORDER_CREATED->value => OrderStatusUpdatedOutboxEventHandler::class,
        OutboxEventType::ORDER_CONFIRMED->value => OrderStatusUpdatedOutboxEventHandler::class,
        OutboxEventType::ORDER_PREPARING->value => OrderStatusUpdatedOutboxEventHandler::class,
        OutboxEventType::ORDER_READY->value => OrderStatusUpdatedOutboxEventHandler::class,
        OutboxEventType::ORDER_PICKED_UP->value => OrderStatusUpdatedOutboxEventHandler::class,
        OutboxEventType::ORDER_OUT_FOR_DELIVERY->value => OrderStatusUpdatedOutboxEventHandler::class,
        OutboxEventType::ORDER_DELIVERED->value => OrderStatusUpdatedOutboxEventHandler::class,
        OutboxEventType::ORDER_CANCELLED->value => OrderStatusUpdatedOutboxEventHandler::class,
    ];

    public function handle(OutboxEventPublished $event): void
    {
        $handlerClass = $this->handlers[$event->eventName] ?? null;

        if (! $handlerClass) {
            return;
        }

        app($handlerClass)->handle($event);
    }
}
