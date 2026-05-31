<?php

namespace Tests\Unit\Services;

use App\Enums\DeliveryOfferEventType;
use App\Enums\NotificationType;
use App\Enums\OrderEventType;
use App\Enums\OutboxEventType;
use App\Enums\PaymentEventType;
use App\Domain\Notifications\OutboxNotificationMapper;
use App\Repositories\ChainManagerRepository\ChainManagerRepositoryInterface;
use App\Repositories\LocalManagerRepository\LocalManagerRepositoryInterface;
use App\Repositories\RestaurantRepository\RestaurantRepositoryInterface;
use PHPUnit\Framework\TestCase;

class NotificationMapperTest extends TestCase
{
    private function mapper(): OutboxNotificationMapper
    {
        return new OutboxNotificationMapper(
            $this->createStub(LocalManagerRepositoryInterface::class),
            $this->createStub(ChainManagerRepositoryInterface::class),
            $this->createStub(RestaurantRepositoryInterface::class),
        );
    }

    public function test_maps_order_ready_event_to_notification_dto(): void
    {
        $dto = $this->mapper()->map(OrderEventType::ORDER_READY, [
            'eventName' => OrderEventType::ORDER_READY->value,
            'orderId' => 'order-1',
            'customerId' => 'customer-1',
            'status' => 'READY',
        ]);

        $this->assertNotNull($dto);
        $this->assertSame('customer-1', $dto->userId);
        $this->assertSame(NotificationType::ORDER_UPDATE, $dto->type);
        $this->assertSame('Pedido pronto', $dto->title);
        $this->assertSame('O seu pedido #ORDER-1 esta pronto para recolha.', $dto->message);
        $this->assertSame('order-1', $dto->data['order_id']);
        $this->assertSame('READY', $dto->data['status']);
    }

    public function test_maps_created_order_with_pending_payment_to_pending_message(): void
    {
        $dto = $this->mapper()->map(OrderEventType::ORDER_CREATED, [
            'eventName' => OrderEventType::ORDER_CREATED->value,
            'orderId' => 'order-1',
            'customerId' => 'customer-1',
            'restaurantName' => 'Fast Pizza',
            'paymentStatus' => 'PENDING',
        ]);

        $this->assertNotNull($dto);
        $this->assertSame('Pedido criado', $dto->title);
        $this->assertSame('O seu pedido #ORDER-1 em Fast Pizza aguarda pagamento.', $dto->message);
    }

    public function test_ignores_created_order_with_completed_payment_to_avoid_duplicate_confirmed_notification(): void
    {
        $this->assertNull($this->mapper()->map(OrderEventType::ORDER_CREATED, [
            'eventName' => OrderEventType::ORDER_CREATED->value,
            'orderId' => 'order-1',
            'customerId' => 'customer-1',
            'restaurantName' => 'Fast Pizza',
            'paymentStatus' => 'COMPLETED',
        ]));
    }

    public function test_order_cancelled_delegate_resolves_dynamic_message_from_payload(): void
    {
        $dto = $this->mapper()->map(OrderEventType::ORDER_CANCELLED, [
            'eventName' => OrderEventType::ORDER_CANCELLED->value,
            'orderId' => 'order-1',
            'customerId' => 'customer-1',
            'status' => 'CANCELLED',
            'data' => [
                'reason' => 'Restaurante indisponivel.',
            ],
        ]);

        $this->assertNotNull($dto);
        $this->assertSame('Pedido cancelado', $dto->title);
        $this->assertSame('O seu pedido #ORDER-1 foi cancelado. Motivo: Restaurante indisponivel.', $dto->message);
        $this->assertSame('Restaurante indisponivel.', $dto->data['reason']);
    }

    public function test_maps_courier_job_offer_to_courier_notification(): void
    {
        $dto = $this->mapper()->map(DeliveryOfferEventType::JOB_OFFERED, [
            'eventName' => DeliveryOfferEventType::JOB_OFFERED->value,
            'offerId' => 'offer-1',
            'deliveryId' => 'delivery-1',
            'courierId' => 'courier-1',
            'expiresAt' => '2026-05-17T21:00:00+00:00',
        ]);

        $this->assertNotNull($dto);
        $this->assertSame('courier-1', $dto->userId);
        $this->assertSame('Nova proposta de entrega', $dto->title);
        $this->assertSame('offer-1', $dto->data['offer_id']);
        $this->assertSame('delivery-1', $dto->data['delivery_id']);
    }

    public function test_maps_chat_message_to_all_participants_except_sender(): void
    {
        $notifications = $this->mapper()->mapAll(OutboxEventType::CHAT_MESSAGE_SENT, [
            'event_name' => OutboxEventType::CHAT_MESSAGE_SENT->value,
            'chat_id' => 'chat-1',
            'order_id' => 'order-1',
            'chat_type' => 'CUSTOMER_RESTAURANT',
            'message_id' => 'message-1',
            'user_id' => 'customer-1',
            'participant_user_ids' => ['customer-1', 'manager-1', 'manager-2', 'manager-2'],
        ]);

        $this->assertCount(2, $notifications);
        $this->assertSame(['manager-1', 'manager-2'], array_column($notifications, 'userId'));
        $this->assertSame(NotificationType::SYSTEM, $notifications[0]->type);
        $this->assertSame('Nova mensagem', $notifications[0]->title);
        $this->assertSame('Tem uma nova mensagem no chat do pedido #ORDER-1.', $notifications[0]->message);
        $this->assertSame('chat-1', $notifications[0]->data['chat_id']);
        $this->assertSame('message-1', $notifications[0]->data['message_id']);
        $this->assertSame('order-1', $notifications[0]->data['order_id']);
        $this->assertSame('CUSTOMER_RESTAURANT', $notifications[0]->data['chat_type']);
    }

    public function test_returns_null_for_events_without_notification(): void
    {
        $this->assertNull($this->mapper()->map(PaymentEventType::PAYMENT_CREATED, []));
    }

    public function test_returns_null_when_required_recipient_is_missing(): void
    {
        $this->assertNull($this->mapper()->map(OrderEventType::ORDER_READY, [
            'eventName' => OrderEventType::ORDER_READY->value,
            'orderId' => 'order-1',
        ]));
    }
}
