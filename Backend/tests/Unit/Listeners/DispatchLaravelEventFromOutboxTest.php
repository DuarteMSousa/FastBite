<?php

namespace Tests\Unit\Listeners;

use App\Enums\OutboxEventType;
use App\Events\CourierAssigned;
use App\Events\DeliveryOffered;
use App\Events\OrderStatusUpdated;
use App\Events\OutboxEventPublished;
use App\Listeners\DispatchLaravelEventFromOutbox;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DispatchLaravelEventFromOutboxTest extends TestCase
{
    public function test_dispatches_laravel_event_for_mapped_outbox_type(): void
    {
        Event::fake([DeliveryOffered::class]);

        (new DispatchLaravelEventFromOutbox())->handle(new OutboxEventPublished(
            OutboxEventType::JOB_OFFERED->value,
            ['courierId' => 'courier-1']
        ));

        Event::assertDispatched(DeliveryOffered::class, function (DeliveryOffered $event): bool {
            return $event->payload['courierId'] === 'courier-1';
        });
    }

    public function test_courier_assigned_also_dispatches_order_status_update(): void
    {
        Event::fake([CourierAssigned::class, OrderStatusUpdated::class]);

        (new DispatchLaravelEventFromOutbox())->handle(new OutboxEventPublished(
            OutboxEventType::ORDER_COURIER_ASSIGNED->value,
            ['orderId' => 'order-1', 'courierId' => 'courier-1']
        ));

        Event::assertDispatched(CourierAssigned::class);
        Event::assertDispatched(OrderStatusUpdated::class);
    }
}
