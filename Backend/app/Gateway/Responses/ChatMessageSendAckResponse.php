<?php

namespace App\Gateway\Responses;

use App\Gateway\SocketServerEventType;

final readonly class ChatMessageSendAckResponse implements SocketResponse
{
    public function __construct(
        public string $chatId,
        public string $messageId,
    ) {
    }

    public function type(): SocketServerEventType
    {
        return SocketServerEventType::CHAT_MESSAGE_SEND_ACK;
    }

    public function payload(): array
    {
        return [
            'chat_id' => $this->chatId,
            'message_id' => $this->messageId,
        ];
    }
}
