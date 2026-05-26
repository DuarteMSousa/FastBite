<?php

namespace App\Services\NotificationService;

use App\DTOs\Notification\CreateNotificationDTO;
use App\Enums\DeliveryEventType;
use App\Enums\DeliveryOfferEventType;
use App\Enums\NotificationType;
use App\Enums\OrderEventType;
use BackedEnum;

class NotificationMapper
{
    /**
     * @var array<string, mixed>
     */
    private array $map;

    public function __construct()
    {
        $this->map = [
            OrderEventType::ORDER_CREATED->value => fn (array $payload): ?CreateNotificationDTO => $this->orderCreated($payload),
            OrderEventType::ORDER_CONFIRMED->value => fn (array $payload): ?CreateNotificationDTO => $this->orderUpdate(
                $payload,
                'Pedido confirmado',
                'teve o pagamento confirmado e avançou.'
            ),
            OrderEventType::ORDER_PREPARING->value => fn (array $payload): ?CreateNotificationDTO => $this->orderUpdate(
                $payload,
                'Pedido em preparação',
                'foi aceite pelo restaurante e começou a ser preparado.'
            ),
            OrderEventType::ORDER_READY->value => fn (array $payload): ?CreateNotificationDTO => $this->orderUpdate(
                $payload,
                'Pedido pronto',
                'está pronto para recolha.'
            ),
            OrderEventType::ORDER_OUT_FOR_DELIVERY->value => fn (array $payload): ?CreateNotificationDTO => $this->orderUpdate(
                $payload,
                'Pedido a caminho',
                'saiu para entrega.'
            ),
            OrderEventType::ORDER_DELIVERED->value => fn (array $payload): ?CreateNotificationDTO => $this->orderUpdate(
                $payload,
                'Pedido entregue',
                'foi entregue.'
            ),
            OrderEventType::ORDER_CANCELLED->value => fn (array $payload): ?CreateNotificationDTO => $this->orderUpdate(
                $payload,
                'Pedido cancelado',
                $this->cancelledOrderMessage($payload)
            ),
            DeliveryOfferEventType::JOB_OFFERED->value => fn (array $payload): ?CreateNotificationDTO => $this->courierJobOffered($payload),
            OrderEventType::ORDER_COURIER_ASSIGNED->value => fn (array $payload): ?CreateNotificationDTO => $this->orderUpdate(
                $payload,
                'Estafeta atribuído',
                'já tem estafeta atribuído.'
            ),
            OrderEventType::ORDER_PICKED_UP->value => fn (array $payload): ?CreateNotificationDTO => $this->orderUpdate(
                $payload,
                'Pedido recolhido',
                'foi recolhido pelo estafeta no restaurante.'
            ),
            DeliveryEventType::DELIVERY_FAILED->value => fn (array $payload): ?CreateNotificationDTO => $this->orderUpdate(
                $payload,
                'Problema na entrega',
                'teve uma falha na entrega e será acompanhado pelo restaurante.'
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function map(BackedEnum $eventType, array $payload): ?CreateNotificationDTO
    {
        $factory = $this->map[$eventType->value] ?? null;

        return $factory ? $factory($payload) : null;
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
            message: 'Tem uma nova entrega disponível para aceitar.',
            data: [
                'offer_id' => $payload['offerId'] ?? null,
                'delivery_id' => $payload['deliveryId'] ?? null,
                'event_name' => $payload['eventName'] ?? DeliveryOfferEventType::JOB_OFFERED->value,
                'expires_at' => $payload['expiresAt'] ?? null,
            ],
        );
    }
}
