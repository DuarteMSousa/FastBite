<?php

namespace App\Repositories\ChatRepository;

use App\DTOs\Chat\CreateOrderChatDTO;
use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;

interface ChatRepositoryInterface
{
    public function findByOrderId(string $orderId);

    public function findById(string $id): ?Chat;

    public function findByIdOrFail(string $id): Chat;

    public function findMessages(string $chatId, int $page, int $perPage);

    public function findParticipants(string $chatId);

    public function createOrderChat(CreateOrderChatDTO $data): Chat;

    public function findParticipant(string $chatId, string $userId): ?ChatParticipant;

    public function addParticipant(string $chatId, string $userId): ChatParticipant;

    public function createMessage(string $chatId, string $participantId, string $content): Message;
}
