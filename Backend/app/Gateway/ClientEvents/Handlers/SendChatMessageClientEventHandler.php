<?php

namespace App\Gateway\ClientEvents\Handlers;

use App\Aspects\ErrorLogged;
use App\Aspects\Logged;
use App\DTOs\Chat\SendMessageDTO;
use App\Enums\OutboxEventType;
use App\Gateway\ClientEvents\ClientEventHandler;
use App\Gateway\ClientEvents\ClientSocketMessage;
use App\Gateway\ClientEvents\ReadsClientPayload;
use App\Gateway\Responses\ChatMessageSendAckResponse;
use App\Gateway\SocketClientEventType;
use App\Gateway\SocketMessage;
use App\Services\ChatService\ChatServiceInterface;
use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Str;

class SendChatMessageClientEventHandler implements ClientEventHandler
{
    use ReadsClientPayload;

    public function __construct(private ChatServiceInterface $chatService) {}

    public function type(): SocketClientEventType
    {
        return SocketClientEventType::CHAT_MESSAGE_SEND;
    }

    #[Logged]
    #[ErrorLogged]
    public function handle(string $clientId, ClientSocketMessage $message): void
    {
        $senderUserId = $this->requiredStringFromMessageOrSession($message, $clientId, 'user_id', 'sender_user_id');
        $clientMessageId = $message->string('client_message_id');
        $data = SendMessageDTO::from([
            'chat_id' => $message->requiredString('chat_id'),
            'content' => $message->requiredString('content'),
        ]);

        $chatMessage = $this->chatService->sendChatMessage(
            $senderUserId,
            $data
        );

        Gateway::sendToGroup(
            "chat.{$data->chat_id}",
            SocketMessage::event(OutboxEventType::CHAT_MESSAGE_SENT->value, "chat.{$data->chat_id}", [
                'event_id' => (string) Str::uuid(),
                'event_name' => OutboxEventType::CHAT_MESSAGE_SENT->value,
                'chat_id' => $data->chat_id,
                'message_id' => $chatMessage->id,
                'user_id' => $senderUserId,
                'content' => $chatMessage->content,
                'timestamp' => $chatMessage->timestamp?->toIso8601String(),
            ])
        );

        Gateway::sendToClient($clientId, SocketMessage::response(
            new ChatMessageSendAckResponse($data->chat_id, $chatMessage->id, $clientMessageId)
        ));
    }
}
