<?php

namespace App\Services\ChatService;

use App\Aspects\Transactional;
use App\DTOs\Chat\CreateOrderChatDTO;
use App\DTOs\Chat\SendMessageDTO;
use App\Enums\OrderStatus;
use App\Enums\OutboxEventName;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Repositories\ChatRepository\ChatRepositoryInterface;
use App\Repositories\UserRepository\UserRepositoryInterface;
use App\Services\OutboxService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatService implements ChatServiceInterface
{
    private ChatRepositoryInterface $chats;

    private UserRepositoryInterface $users;

    public function __construct(
        ?ChatRepositoryInterface $chats = null,
        ?UserRepositoryInterface $users = null,
    ) {
        $this->chats = $chats ?? app(ChatRepositoryInterface::class);
        $this->users = $users ?? app(UserRepositoryInterface::class);
    }

    public function getChatsByOrderId(string $orderId)
    {
        return $this->chats->findByOrderId($orderId);
    }

    public function getChatById(string $id): ?Chat
    {
        return $this->chats->findById($id);
    }

    public function getMessagesByChatId(string $chatId, int $page, int $perPage)
    {
        return $this->chats->findMessages($chatId, $page, $perPage);
    }

    public function getParticipantsByChatId(string $chatId)
    {
        return $this->chats->findParticipants($chatId);
    }

    #[Transactional]
    public function createOrderChat(CreateOrderChatDTO $data): Chat
    {
        foreach ($data->participant_user_ids as $userId) {
            $this->users->findById($userId) ?? throw ValidationException::withMessages([
                'participant_user_ids' => 'One or more users do not exist.',
            ]);
        }

        return $this->chats->createOrderChat($data);
    }

    #[Transactional]
    public function sendChatMessage(string $senderUserId, SendMessageDTO $data): Message
    {
        $chat = $this->chats->findByIdOrFail($data->chat_id);

        if ($chat->closed_at !== null) {
            throw ValidationException::withMessages([
                'chat_id' => 'Chat is closed.',
            ]);
        }

        if ($chat->order && $chat->order->status === OrderStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'chat_id' => 'Order was cancelled. Chat is no longer available.',
            ]);
        }

        $participant = $this->chats->findParticipant($data->chat_id, $senderUserId);

        // Auto-adicionar managers autorizados que ainda nao sejam participantes.
        // Espelha a logica de authorization do canal broadcast chat.{chatId}.
        if (! $participant) {
            $sender = $this->users->findById($senderUserId);

            if (! $sender) {
                throw ValidationException::withMessages([
                    'sender_user_id' => 'User does not exist.',
                ]);
            }

            if (! $this->isAuthorizedManagerForChat($sender, $chat)) {
                throw ValidationException::withMessages([
                    'sender_user_id' => 'User is not a participant of this chat.',
                ]);
            }

            $participant = $this->chats->addParticipant($data->chat_id, $sender->id);
        }

        $message = $this->chats->createMessage($data->chat_id, $participant->id, $data->content);

        app(OutboxService::class)->enqueue('chat', $chat->id, OutboxEventName::CHAT_MESSAGE_SENT->value, [
            'eventId' => (string) Str::uuid(),
            'eventName' => OutboxEventName::CHAT_MESSAGE_SENT->value,
            'chatId' => $chat->id,
            'messageId' => $message->id,
            'senderUserId' => $senderUserId,
            'senderParticipantId' => $participant->id,
            'content' => $message->content,
            'sentAt' => $message->timestamp?->toIso8601String(),
        ]);

        return $message;
    }

    private function isAuthorizedManagerForChat(User $user, Chat $chat): bool
    {
        $restaurant = $chat->order?->restaurant;

        if (! $restaurant) {
            return false;
        }

        if ($user->isLocalManager()) {
            return $restaurant->localManager
                && $restaurant->localManager->user_id === $user->id;
        }

        if ($user->isChainManager()) {
            return $restaurant->chain
                && $restaurant->chain->chainManagers->contains('user_id', $user->id);
        }

        return false;
    }
}
