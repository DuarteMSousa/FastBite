<?php

namespace App\Gateway\ServerEvents;

class SocketMessageDispatcher
{
    /**
     * @var array<int, class-string<SocketEventHandler>>
     */
    private array $handlers = [
        Handlers\ChatMessageSentSocketHandler::class,
        Handlers\CourierPositionUpdatedSocketHandler::class,
        Handlers\UserNotificationCreatedSocketHandler::class,
        Handlers\DeliveryOfferedSocketHandler::class,
        Handlers\CourierAssignedSocketHandler::class,
        Handlers\OrderStatusUpdatedSocketHandler::class,
    ];

    public function dispatch(object $event): void
    {
        foreach ($this->handlers as $handlerClass) {
            $handler = app($handlerClass);

            if ($handler->supports($event)) {
                $handler->handle($event);
            }
        }
    }
}
