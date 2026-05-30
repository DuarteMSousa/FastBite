<?php

namespace App\Services;

use App\DTOs\Outbox\CreateOutboxEventDTO;
use App\Enums\OutboxAggregateType;
use App\Enums\OutboxEventType;
use App\Jobs\PublishOutboxEventJob;
use App\Models\OutboxEvent;
use App\Repositories\OutboxRepository\OutboxRepositoryInterface;

class OutboxService
{
    public function __construct(private OutboxRepositoryInterface $outboxRepository) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function enqueue(
        OutboxAggregateType $aggregateType,
        ?string $aggregateId,
        OutboxEventType $eventType,
        array $payload,
        bool $dispatchNow = true
    ): OutboxEvent {
        $outbox = $this->outboxRepository->createOutboxEvent(new CreateOutboxEventDTO(
            aggregateType: $aggregateType,
            aggregateId: $aggregateId,
            eventType: $eventType,
            payload: $payload,
        ));

        if ($dispatchNow) {
            PublishOutboxEventJob::dispatch($outbox->id)->afterCommit();
        }

        return $outbox;
    }
}
