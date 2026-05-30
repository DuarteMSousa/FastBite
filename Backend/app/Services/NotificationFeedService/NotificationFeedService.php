<?php

namespace App\Services\NotificationFeedService;

use App\Aspects\Transactional;
use App\DTOs\Notification\RegisterPushTokenDTO;
use App\Repositories\NotificationRepository\NotificationRepositoryInterface;
use App\Repositories\UserPushTokenRepository\UserPushTokenRepositoryInterface;

class NotificationFeedService implements NotificationFeedServiceInterface
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private UserPushTokenRepositoryInterface $userPushTokenRepository,
    ) {}

    public function getNotificationsByUserId(string $userId, bool $unreadOnly = false, int $page = 1, int $perPage = 50): array
    {
        $notifications = $this->notificationRepository->getByUserId($userId, $unreadOnly, $page, $perPage);

        return [
            'items' => $notifications->items(),
            'current_page' => $notifications->currentPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'last_page' => $notifications->lastPage(),
        ];
    }

    #[Transactional]
    public function markNotificationAsRead(string $userId, string $notificationId): array
    {
        $notification = $this->notificationRepository->markAsReadByUserId($userId, $notificationId);

        return [
            'ok' => true,
            'notification_id' => $notification->id,
            'read_at' => $notification->read_at?->toIso8601String(),
        ];
    }

    #[Transactional]
    public function markAllNotificationsAsRead(string $userId): array
    {
        $affected = $this->notificationRepository->markAllAsReadByUserId($userId);

        return [
            'ok' => true,
            'affected_count' => $affected,
        ];
    }

    #[Transactional]
    public function registerPushToken(string $userId, RegisterPushTokenDTO $data): array
    {
        $token = $this->userPushTokenRepository->upsertByUserId($userId, $data);

        return [
            'ok' => true,
            'push_token_id' => $token->id,
            'is_active' => $token->is_active,
        ];
    }

}
