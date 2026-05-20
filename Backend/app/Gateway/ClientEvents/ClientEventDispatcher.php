<?php

namespace App\Gateway\ClientEvents;

use App\Gateway\ClientEvents\Handlers\HelloClientEventHandler;
use App\Gateway\ClientEvents\Handlers\SendChatMessageClientEventHandler;
use App\Gateway\ClientEvents\Handlers\SubscribeClientEventHandler;
use App\Gateway\ClientEvents\Handlers\UnsubscribeClientEventHandler;
use App\Gateway\ClientEvents\Handlers\UpdateCourierPositionClientEventHandler;
use App\Gateway\ClientEvents\Handlers\UpdateCourierStatusClientEventHandler;
use Illuminate\Validation\ValidationException;

class ClientEventDispatcher
{
    /**
     * @var array<string, class-string<ClientEventHandler>>
     */
    private array $handlersByType = [
        SocketClientEventType::HELLO->value => HelloClientEventHandler::class,
        SocketClientEventType::SUBSCRIBE->value => SubscribeClientEventHandler::class,
        SocketClientEventType::UNSUBSCRIBE->value => UnsubscribeClientEventHandler::class,
        SocketClientEventType::CHAT_MESSAGE_SEND->value => SendChatMessageClientEventHandler::class,
        SocketClientEventType::COURIER_STATUS_SET->value => UpdateCourierStatusClientEventHandler::class,
        SocketClientEventType::COURIER_POSITION_SET->value => UpdateCourierPositionClientEventHandler::class,
    ];

    public function dispatch(string $clientId, string $message): void
    {
        $socketMessage = ClientSocketMessage::fromJson($message);
        $handlerClass = $this->handlersByType[$socketMessage->type->value] ?? null;

        if (! $handlerClass) {
            throw ValidationException::withMessages([
                'type' => 'No handler registered for this message type.',
            ]);
        }

        app($handlerClass)->handle($clientId, $socketMessage);
    }
}
