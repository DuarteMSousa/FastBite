<?php

namespace App\Services\ChatService;

use App\Aspects\Transactional;
use App\DTOs\Chat\CreateOrderChatDTO;
use App\DTOs\Chat\SendMessageDTO;
use App\Enums\ChatType;
use App\Enums\OrderStatus;
use App\Enums\OutboxAggregateType;
use App\Enums\OutboxEventType;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Repositories\ChatRepository\ChatRepositoryInterface;
use App\Repositories\OrderRepository\OrderRepositoryInterface;
use App\Repositories\UserRepository\UserRepositoryInterface;
use App\Services\OutboxService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatService implements ChatServiceInterface
{
    private ChatRepositoryInterface $chatRepository;

    private UserRepositoryInterface $userRepository;

    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        ?ChatRepositoryInterface $chatRepository = null,
        ?UserRepositoryInterface $userRepository = null,
        ?OrderRepositoryInterface $orderRepository = null,
    ) {
        $this->chatRepository = $chatRepository ?? app(ChatRepositoryInterface::class);
        $this->userRepository = $userRepository ?? app(UserRepositoryInterface::class);
        $this->orderRepository = $orderRepository ?? app(OrderRepositoryInterface::class);
    }

    public function getChatsByOrderId(string $orderId)
    {
        return $this->chatRepository->findByOrderId($orderId);
    }

    public function getMessagesByChatId(string $chatId, int $page, int $perPage)
    {
        return $this->chatRepository->findMessages($chatId, $page, $perPage);
    }

    public function getParticipantsByChatId(string $chatId)
    {
        return $this->chatRepository->findParticipants($chatId);
    }

    #[Transactional]
    public function createOrderChat(CreateOrderChatDTO $data): Chat
    {
        $participantUserIds = $this->resolveOrderChatParticipants($data);

        foreach ($participantUserIds as $userId) {
            $this->userRepository->findById($userId) ?? throw ValidationException::withMessages([
                'participant_user_ids' => 'One or more users do not exist.',
            ]);
        }

        if (count($participantUserIds) < 2) {
            throw ValidationException::withMessages([
                'participant_user_ids' => 'The chat needs at least two participants.',
            ]);
        }

        return $this->chatRepository->createOrderChat(new CreateOrderChatDTO(
            order_id: $data->order_id,
            type: $data->type,
            participant_user_ids: $participantUserIds,
        ));
    }

    #[Transactional]
    public function sendChatMessage(string $senderUserId, SendMessageDTO $data): Message
    {
        $chat = $this->chatRepository->findByIdOrFail($data->chat_id);

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

        if (! $this->chatRepository->findParticipant($data->chat_id, $senderUserId)) {
            $sender = $this->userRepository->findById($senderUserId);

            if (! $sender) {
                throw ValidationException::withMessages([
                    'user_id' => 'User does not exist.',
                ]);
            }

            if (! $this->isAuthorizedManagerForChat($sender, $chat)) {
                throw ValidationException::withMessages([
                    'user_id' => 'User is not a participant of this chat.',
                ]);
            }

            $this->chatRepository->addParticipant($data->chat_id, $sender->id);
        }

        $message = $this->chatRepository->createMessage($data->chat_id, $senderUserId, $data->content);
        $participantUserIds = $this->chatRepository
            ->findParticipants($data->chat_id)
            ->pluck('user_id')
            ->map(static fn ($userId): string => (string) $userId)
            ->unique()
            ->values()
            ->all();

        app(OutboxService::class)->enqueue(OutboxAggregateType::CHAT, $chat->id, OutboxEventType::CHAT_MESSAGE_SENT, [
            'event_id' => (string) Str::uuid(),
            'event_name' => OutboxEventType::CHAT_MESSAGE_SENT->value,
            'chat_id' => $chat->id,
            'order_id' => $chat->order_id,
            'chat_type' => $chat->type?->value,
            'message_id' => $message->id,
            'user_id' => $senderUserId,
            'participant_user_ids' => $participantUserIds,
            'content' => $message->content,
            'timestamp' => $message->timestamp?->toIso8601String(),
        ]);

        return $message;
    }

    /**
     * @return array<int, string>
     */
    private function resolveOrderChatParticipants(CreateOrderChatDTO $data): array
    {
        $order = $this->orderRepository->findForChatParticipantsOrFail($data->order_id);

        $participantUserIds = collect($data->participant_user_ids)
            ->push($order->user_id);

        if ($data->type === ChatType::CUSTOMER_RESTAURANT) {
            $participantUserIds->push($order->restaurant?->localManager?->user_id);

            foreach ($order->restaurant?->chain?->chainManagers ?? [] as $manager) {
                $participantUserIds->push($manager->user_id);
            }
        }

        if ($data->type === ChatType::CUSTOMER_COURIER) {
            $participantUserIds->push($order->delivery?->courier_id);
        }

        return $participantUserIds
            ->filter()
            ->map(static fn ($userId): string => (string) $userId)
            ->unique()
            ->values()
            ->all();
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
