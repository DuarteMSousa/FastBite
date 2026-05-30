<?php

namespace Tests\Unit\Gateway;

use App\Events\OrderStatusUpdated;
use App\Gateway\GatewayClientSocketPusher;
use App\Gateway\ServerEvents\Handlers\OrderStatusUpdatedSocketHandler;
use Mockery;
use Tests\TestCase;

class OrderStatusUpdatedSocketHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_does_not_publish_unassigned_order_to_restaurant_topic(): void
    {
        $payload = [
            'eventName' => 'ORDER_CREATED',
            'orderId' => 'order-1',
            'customerId' => 'customer-1',
            'restaurantId' => 'restaurant-1',
            'order' => [
                'id' => 'order-1',
                'delivery' => null,
            ],
        ];

        $pusher = Mockery::mock(GatewayClientSocketPusher::class);
        $pusher
            ->shouldReceive('sendToGroup')
            ->once()
            ->with('order.order-1.tracking', 'ORDER_STATUS_UPDATED', $payload);
        $pusher
            ->shouldReceive('sendToGroup')
            ->once()
            ->with('customer.customer-1.orders', 'ORDER_STATUS_UPDATED', $payload);
        $pusher
            ->shouldReceive('sendToGroup')
            ->never()
            ->with('restaurant.restaurant-1.orders', 'ORDER_STATUS_UPDATED', Mockery::type('array'));

        (new OrderStatusUpdatedSocketHandler($pusher))->handle(new OrderStatusUpdated($payload));

        $this->addToAssertionCount(1);
    }

    public function test_publishes_assigned_order_to_restaurant_topic(): void
    {
        $payload = [
            'eventName' => 'ORDER_COURIER_ASSIGNED',
            'orderId' => 'order-1',
            'customerId' => 'customer-1',
            'restaurantId' => 'restaurant-1',
            'order' => [
                'id' => 'order-1',
                'delivery' => [
                    'courier_id' => 'courier-1',
                ],
            ],
        ];

        $pusher = Mockery::mock(GatewayClientSocketPusher::class);
        $pusher
            ->shouldReceive('sendToGroup')
            ->once()
            ->with('order.order-1.tracking', 'ORDER_STATUS_UPDATED', $payload);
        $pusher
            ->shouldReceive('sendToGroup')
            ->once()
            ->with('customer.customer-1.orders', 'ORDER_STATUS_UPDATED', $payload);
        $pusher
            ->shouldReceive('sendToGroup')
            ->once()
            ->with('restaurant.restaurant-1.orders', 'ORDER_STATUS_UPDATED', $payload);

        (new OrderStatusUpdatedSocketHandler($pusher))->handle(new OrderStatusUpdated($payload));

        $this->addToAssertionCount(1);
    }
}
