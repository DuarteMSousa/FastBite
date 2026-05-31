<?php

namespace App\Services\NotificationService;

use App\Aspects\Transactional;
use App\Domain\Notifications\OutboxNotificationMapper;
use App\DTOs\Notification\CreateNotificationDTO;
use App\Enums\NotificationType;
use App\Enums\OutboxAggregateType;
use App\Enums\OutboxEventType;
use App\Enums\PushTokenProvider;
use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use App\Notifications\UserSystemNotification;
use App\Repositories\NotificationRepository\NotificationRepositoryInterface;
use App\Repositories\UserPushTokenRepository\UserPushTokenRepositoryInterface;
use App\Services\OutboxService;
use BackedEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private OutboxService $outboxService,
        private OutboxNotificationMapper $mapper,
        private NotificationRepositoryInterface $notificationRepository,
        private UserPushTokenRepositoryInterface $userPushTokenRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    #[Transactional]
    public function createAndDispatch(
        string $userId,
        NotificationType $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        return $this->createFromDTO(new CreateNotificationDTO(
            userId: $userId,
            type: $type,
            title: $title,
            message: $message,
            data: $data
        ));
    }


    /**
     * @param  array<string, mixed>  $payload
     * @return Notification[]
     */
    #[Transactional]
    public function createAllFromEvent(BackedEnum $eventType, array $payload): array
    {
        $dtos = $this->mapper->mapAll($eventType, $payload);
        $notifications = [];

        foreach ($dtos as $dto) {
            $notifications[] = $this->createFromDTO($dto);
        }

        return $notifications;
    }

    public function dispatchChannels(string $notificationId, array $payload): void
    {
        $notification = $this->notificationRepository->getDispatchableById($notificationId);

        if (! $notification) {
            return;
        }

        if ($notification->user?->email) {
            $notification->user->notify(new UserSystemNotification($payload));
        }

        $userPushTokenRepository = $this->userPushTokenRepository->getActiveByUserId($notification->user_id);

        foreach ($userPushTokenRepository as $pushToken) {
            SendPushNotificationJob::dispatch($pushToken->id, $payload);
        }

        Log::info('notification.channels.dispatched', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'email' => (bool) $notification->user?->email,
            'push_tokens' => $userPushTokenRepository->count(),
            'type' => $notification->type->value,
        ]);
    }

    public function sendPushNotification(string $pushTokenId, array $payload): void
    {
        $pushToken = $this->userPushTokenRepository->getById($pushTokenId);

        if (! $pushToken || ! $pushToken->is_active) {
            return;
        }

        if ($pushToken->provider !== PushTokenProvider::EXPO) {
            Log::warning('push.provider.unsupported', [
                'push_token_id' => $pushToken->id,
                'provider' => $pushToken->provider->value,
            ]);

            return;
        }

        $response = Http::timeout((int) config('services.expo.timeout_seconds', 8))
            ->acceptJson()
            ->post((string) config('services.expo.push_url'), [
                'to' => $pushToken->token,
                'title' => (string) ($payload['title'] ?? 'FastBite'),
                'body' => (string) ($payload['message'] ?? ''),
                'data' => [
                    'notification_id' => $payload['notificationId'] ?? null,
                    'type' => $payload['type'] ?? 'SYSTEM',
                    'meta' => $payload['data'] ?? [],
                ],
            ]);

        if ($this->responseMarksTokenInvalid($response->json())) {
            $this->userPushTokenRepository->deactivate($pushToken);

            Log::warning('push.token.deactivated', [
                'push_token_id' => $pushToken->id,
                'reason' => 'DeviceNotRegistered',
            ]);

            return;
        }

        if (! $response->successful() || $this->responseHasPushError($response->json())) {
            Log::warning('push.send.failed', [
                'push_token_id' => $pushToken->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return;
        }

        $this->userPushTokenRepository->markUsed($pushToken);
    }

    private function responseHasPushError(?array $body): bool
    {
        return ($body['data']['status'] ?? null) === 'error';
    }

    private function responseMarksTokenInvalid(?array $body): bool
    {
        return ($body['data']['details']['error'] ?? null) === 'DeviceNotRegistered';
    }

    private function createFromDTO(CreateNotificationDTO $dto): Notification
    {
        $notification = $this->notificationRepository->createNotification($dto);
        $sentAt = $notification->sent_at ?? now();

        $payload = [
            'eventId' => (string) Str::uuid(),
            'eventName' => OutboxEventType::USER_NOTIFICATION_CREATED->value,
            'notificationId' => $notification->id,
            'userId' => $notification->user_id,
            'type' => $notification->type->value,
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => $dto->data,
            'sentAt' => $sentAt->toIso8601String(),
        ];

        $this->outboxService->enqueue(
            aggregateType: OutboxAggregateType::NOTIFICATION,
            aggregateId: $notification->id,
            eventType: OutboxEventType::USER_NOTIFICATION_CREATED,
            payload: $payload
        );

        return $notification;
    }
}
