<?php

namespace App\Gateway\ServerEvents\Handlers;

use App\Enums\OutboxEventType;
use App\Events\ChatMessageSent;
use App\Gateway\GatewayClientSocketPusher;
use App\Gateway\ServerEvents\SocketEventHandler;

class ChatMessageSentSocketHandler implements SocketEventHandler
{
    public function __construct(private GatewayClientSocketPusher $pusher) {}

    public function supports(object $event): bool
    {
        return $event instanceof ChatMessageSent;
    }

    public function handle(object $event): void
    {
        /** @var ChatMessageSent $event */
        $chatId = $event->payload['chat_id'] ?? null;

        if (! $chatId) {
            return;
        }

        $this->pusher->sendToGroup(
            "chat.{$chatId}",
            OutboxEventType::CHAT_MESSAGE_SENT->value,
            $event->payload
        );
    }
}
