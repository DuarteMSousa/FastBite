<?php

namespace App\Domain\Notifications;

use App\DTOs\Notification\CreateNotificationDTO;
use App\Enums\DeliveryEventType;
use App\Enums\DeliveryOfferEventType;
use App\Enums\NotificationType;
use App\Enums\OrderEventType;
use App\Models\ChainManager;
use App\Models\LocalManager;
use App\Models\Restaurant;
use BackedEnum;
use Closure;

class OutboxNotificationMapper
{
    /**
     * @var array<string, Closure(array<string, mixed>): ?CreateNotificationDTO>
     */
    private array $notificationDelegates;

    /**
     * @var array<string, Closure(array<string, mixed>): CreateNotificationDTO|array|null>
     */
    private array $restaurantDelegates;

    public function __construct()
    {
        $this->notificationDelegates = [
            OrderEventType::ORDER_CREATED->value => function (array $payload): ?CreateNotificationDTO {
                return $this->orderCreated($payload);
            },
            OrderEventType::ORDER_CONFIRMED->value => $this->orderUpdateDelegate(
                'Pedido confirmado',
                'teve o pagamento confirmado e avancou.'
            ),
            OrderEventType::ORDER_PREPARING->value => $this->orderUpdateDelegate(
                'Pedido em preparacao',
                'foi aceite pelo restaurante e comecou a ser preparado.'
            ),
            OrderEventType::ORDER_READY->value => $this->orderUpdateDelegate(
                'Pedido pronto',
                'esta pronto para recolha.'
            ),
            OrderEventType::ORDER_OUT_FOR_DELIVERY->value => $this->orderUpdateDelegate(
                'Pedido a caminho',
                'saiu para entrega.'
            ),
            OrderEventType::ORDER_DELIVERED->value => $this->orderUpdateDelegate(
                'Pedido entregue',
                'foi entregue.'
            ),
            OrderEventType::ORDER_CANCELLED->value => $this->orderUpdateDelegate(
                'Pedido cancelado',
                fn (array $payload): string => $this->cancelledOrderMessage($payload)
            ),
            DeliveryOfferEventType::JOB_OFFERED->value => fn (array $payload): ?CreateNotificationDTO => $this->courierJobOffered($payload),
            OrderEventType::ORDER_COURIER_ASSIGNED->value => $this->orderUpdateDelegate(
                'Estafeta atribuido',
                'ja tem estafeta atribuido.'
            ),
            OrderEventType::ORDER_PICKED_UP->value => $this->orderUpdateDelegate(
                'Pedido recolhido',
                'foi recolhido pelo estafeta no restaurante.'
            ),
            DeliveryEventType::DELIVERY_FAILED->value => $this->orderUpdateDelegate(
                'Problema na entrega',
                'teve uma falha na entrega e sera acompanhado pelo restaurante.'
            ),
        ];

        $this->restaurantDelegates = [
            OrderEventType::ORDER_CONFIRMED->value => $this->restaurantOrderDelegate(
                'Nova encomenda recebida',
                'tem uma nova encomenda paga e pronta para preparar.'
            ),
            OrderEventType::ORDER_COURIER_ASSIGNED->value => $this->restaurantOrderDelegate(
                'Estafeta atribuido',
                'ja tem estafeta atribuido para recolha.'
            ),
            OrderEventType::ORDER_DELIVERED->value => $this->restaurantOrderDelegate(
                'Encomenda entregue',
                'foi entregue ao cliente com sucesso.'
            ),
            OrderEventType::ORDER_CANCELLED->value => $this->restaurantOrderDelegate(
                'Encomenda cancelada',
                fn (array $payload): string => $this->cancelledOrderMessage($payload)
            ),
            DeliveryEventType::DELIVERY_FAILED->value => $this->restaurantOrderDelegate(
                'Falha na entrega',
                'teve uma falha na entrega. Necessita de atencao.'
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return CreateNotificationDTO[]
     */
    public function mapAll(BackedEnum $eventType, array $payload): array
    {
        $notifications = [];

        $customerDelegate = $this->notificationDelegates[$eventType->value] ?? null;
        if ($customerDelegate) {
            $dto = $customerDelegate($payload);
            if ($dto) {
                $notifications[] = $dto;
            }
        }

        $restaurantDelegate = $this->restaurantDelegates[$eventType->value] ?? null;
        if ($restaurantDelegate) {
            $dtos = $restaurantDelegate($payload);
            foreach (is_array($dtos) ? $dtos : [$dtos] as $dto) {
                if ($dto) {
                    $notifications[] = $dto;
                }
            }
        }

        return $notifications;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @deprecated Use mapAll() instead
     */
    public function map(BackedEnum $eventType, array $payload): ?CreateNotificationDTO
    {
        $delegate = $this->notificationDelegates[$eventType->value] ?? null;

        return $delegate ? $delegate($payload) : null;
    }

    private function orderUpdateDelegate(string $title, string|Closure $message): Closure
    {
        return function (array $payload) use ($title, $message): ?CreateNotificationDTO {
            $resolvedMessage = $message instanceof Closure ? $message($payload) : $message;

            return $this->orderUpdate($payload, $title, $resolvedMessage);
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function orderCreated(array $payload): ?CreateNotificationDTO
    {
        $paymentStatus = $payload['paymentStatus'] ?? $payload['data']['paymentStatus'] ?? null;

        if ($paymentStatus === 'COMPLETED') {
            return null;
        }

        $restaurantName = $payload['restaurantName'] ?? 'o restaurante';

        return $this->orderUpdate(
            $payload,
            'Pedido criado',
            "em {$restaurantName} aguarda pagamento."
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function orderUpdate(array $payload, string $title, string $message): ?CreateNotificationDTO
    {
        $userId = $payload['customerId'] ?? $payload['userId'] ?? null;

        if (! $userId) {
            return null;
        }

        return new CreateNotificationDTO(
            userId: (string) $userId,
            type: NotificationType::ORDER_UPDATE,
            title: $title,
            message: $this->orderMessage($payload, $message),
            data: [
                'order_id' => $payload['orderId'] ?? null,
                'delivery_id' => $payload['deliveryId'] ?? null,
                'event_name' => $payload['eventName'] ?? null,
                'status' => $payload['status'] ?? null,
                'reason' => $payload['data']['reason'] ?? $payload['data']['failure_reason'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function orderMessage(array $payload, string $message): string
    {
        return sprintf('O seu %s %s', $this->orderReference($payload), $message);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function orderReference(array $payload): string
    {
        $orderId = (string) ($payload['orderId'] ?? $payload['order_id'] ?? $payload['data']['order_id'] ?? '');

        if ($orderId === '') {
            return 'pedido';
        }

        return 'pedido #'.strtoupper(substr($orderId, 0, 8));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function cancelledOrderMessage(array $payload): string
    {
        $reason = trim((string) ($payload['data']['reason'] ?? ''));

        if ($reason === '') {
            return 'foi cancelado.';
        }

        return 'foi cancelado. Motivo: '.rtrim($reason, ". \t\n\r\0\x0B").'.';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function courierJobOffered(array $payload): ?CreateNotificationDTO
    {
        $courierId = $payload['courierId'] ?? null;

        if (! $courierId) {
            return null;
        }

        return new CreateNotificationDTO(
            userId: (string) $courierId,
            type: NotificationType::ORDER_UPDATE,
            title: 'Nova proposta de entrega',
            message: 'Tem uma nova entrega disponivel para aceitar.',
            data: [
                'offer_id' => $payload['offerId'] ?? null,
                'delivery_id' => $payload['deliveryId'] ?? null,
                'event_name' => $payload['eventName'] ?? DeliveryOfferEventType::JOB_OFFERED->value,
                'expires_at' => $payload['expiresAt'] ?? null,
            ],
        );
    }

    private function restaurantOrderDelegate(string $title, string|Closure $message): Closure
    {
        return function (array $payload) use ($title, $message): ?CreateNotificationDTO {
            $resolvedMessage = $message instanceof Closure ? $message($payload) : $message;

            return $this->restaurantOrderUpdate($payload, $title, $resolvedMessage);
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return CreateNotificationDTO[]
     */
    private function restaurantOrderUpdate(array $payload, string $title, string $message): array
    {
        $restaurantId = $payload['restaurantId'] ?? $payload['restaurant_id'] ?? null;

        if (! $restaurantId) {
            return [];
        }

        $managerIds = $this->resolveRestaurantManagerIds((string) $restaurantId);

        if ($managerIds === []) {
            return [];
        }

        return array_map(
            fn (string $managerId): CreateNotificationDTO => new CreateNotificationDTO(
                userId: $managerId,
                type: NotificationType::ORDER_UPDATE,
                title: $title,
                message: sprintf('O %s %s', $this->orderReference($payload), $message),
                data: [
                    'order_id' => $payload['orderId'] ?? null,
                    'delivery_id' => $payload['deliveryId'] ?? null,
                    'event_name' => $payload['eventName'] ?? null,
                    'status' => $payload['status'] ?? null,
                    'restaurant_id' => $restaurantId,
                ],
            ),
            $managerIds
        );
    }

    /**
     * @return string[]
     */
    private function resolveRestaurantManagerIds(string $restaurantId): array
    {
        $managerIds = LocalManager::query()
            ->where('restaurant_id', $restaurantId)
            ->pluck('user_id')
            ->all();

        $chainId = Restaurant::query()
            ->whereKey($restaurantId)
            ->value('chain_id');

        if ($chainId) {
            $managerIds = [
                ...$managerIds,
                ...ChainManager::query()
                    ->where('chain_id', $chainId)
                    ->pluck('user_id')
                    ->all(),
            ];
        }

        return array_values(array_unique(array_map('strval', $managerIds)));
    }
}
