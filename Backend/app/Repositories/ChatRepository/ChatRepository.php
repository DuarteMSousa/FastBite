<?php

namespace App\Repositories\ChatRepository;

use App\DTOs\Chat\CreateOrderChatDTO;
use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;

class ChatRepository implements ChatRepositoryInterface
{
    public function findByOrderId(string $orderId)
    {
        return Chat::with(['participants', 'messages'])
            ->where('order_id', $orderId)
            ->orderBy('created_at')
            ->get();
    }

    public function findById(string $id): ?Chat
    {
        return Chat::with(['participants', 'messages'])->find($id);
    }

    public function findByIdOrFail(string $id): Chat
    {
        return Chat::with(['order.restaurant.localManager', 'order.restaurant.chain.chainManagers'])->findOrFail($id);
    }

    public function findMessages(string $chatId, int $page, int $perPage)
    {
        return Message::where('chat_id', $chatId)
            ->orderByDesc('timestamp')
            ->paginate($perPage, ['*'], 'page', $page)
            ->items();
    }

    public function findParticipants(string $chatId)
    {
        return ChatParticipant::where('chat_id', $chatId)
            ->orderBy('joined_at')
            ->get();
    }

    public function createOrderChat(CreateOrderChatDTO $data): Chat
    {
        $chat = Chat::create([
            'order_id' => $data->order_id,
            'type' => $data->type->value,
        ]);

        foreach ($data->participant_user_ids as $userId) {
            $this->addParticipant($chat->id, $userId);
        }

        return $chat->load(['participants', 'messages']);
    }

    public function findParticipant(string $chatId, string $userId): ?ChatParticipant
    {
        return ChatParticipant::where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->first();
    }

    public function addParticipant(string $chatId, string $userId): ChatParticipant
    {
        return ChatParticipant::create([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'joined_at' => now(),
        ]);
    }

    public function createMessage(string $chatId, string $participantId, string $content): Message
    {
        return Message::create([
            'chat_id' => $chatId,
            'sender_participant_id' => $participantId,
            'content' => $content,
            'timestamp' => now(),
        ]);
    }
}
