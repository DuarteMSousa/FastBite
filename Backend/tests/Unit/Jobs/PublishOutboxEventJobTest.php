<?php

namespace Tests\Unit\Jobs;

use App\Enums\OutboxStatus;
use App\Gateway\GatewayClientSocketPusher;
use App\Jobs\PublishOutboxEventJob;
use App\Models\OutboxEvent;
use App\Repositories\OutboxRepository\OutboxRepositoryInterface;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PublishOutboxEventJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_socket_failure_keeps_outbox_event_retryable(): void
    {
        $exception = new RuntimeException('gateway down');
        $outbox = new OutboxEvent;
        $outbox->forceFill([
            'id' => 'outbox-1',
            'aggregate_type' => 'order',
            'aggregate_id' => 'order-1',
            'event_type' => 'ORDER_COURIER_ASSIGNED',
            'payload' => [
                'eventName' => 'ORDER_COURIER_ASSIGNED',
                'channels' => ['restaurant.restaurant-1.orders'],
                'orderId' => 'order-1',
            ],
            'status' => OutboxStatus::PENDING,
        ]);

        $repository = Mockery::mock(OutboxRepositoryInterface::class);
        $repository
            ->shouldReceive('getById')
            ->once()
            ->with('outbox-1')
            ->andReturn($outbox);
        $repository
            ->shouldReceive('markProcessing')
            ->once()
            ->with($outbox)
            ->andReturn($outbox);
        $repository
            ->shouldReceive('markPublished')
            ->never();
        $repository
            ->shouldReceive('markRetryAfterFailure')
            ->once()
            ->with($outbox, Mockery::on(static fn ($actual): bool => $actual === $exception))
            ->andReturn($outbox);

        $this->app->instance(OutboxRepositoryInterface::class, $repository);

        $pusher = Mockery::mock(GatewayClientSocketPusher::class);
        $pusher
            ->shouldReceive('sendToGroup')
            ->once()
            ->with(
                'restaurant.restaurant-1.orders',
                'ORDER_COURIER_ASSIGNED',
                Mockery::type('array')
            )
            ->andThrow($exception);

        $this->app->instance(GatewayClientSocketPusher::class, $pusher);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('gateway down');

        (new PublishOutboxEventJob('outbox-1'))->handle();
    }
}
