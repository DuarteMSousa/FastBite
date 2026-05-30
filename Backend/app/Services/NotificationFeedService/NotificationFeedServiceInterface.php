<?php

namespace App\Services\NotificationFeedService;

use App\DTOs\Notification\RegisterPushTokenDTO;

interface NotificationFeedServiceInterface
{
    public function getNotificationsByUserId(string $userId, bool $unreadOnly = false, int $page = 1, int $perPage = 50): array;

    public function markNotificationAsRead(string $userId, string $notificationId): array;

    public function markAllNotificationsAsRead(string $userId): array;

    public function registerPushToken(string $userId, RegisterPushTokenDTO $data): array;
}
