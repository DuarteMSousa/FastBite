<?php

namespace App\Services;

use App\DTOs\Outbox\CreateOutboxEventDTO;
use App\Enums\OutboxAggregateType;
use App\Enums\OutboxEventType;
use App\Enums\OutboxStatus;
use App\Events\OutboxEventPublished;
use App\Jobs\PublishOutboxEventJob;
use App\Models\OutboxEvent;
use App\Repositories\OutboxRepository\OutboxRepositoryInterface;
use Throwable;

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

    public function publishById(string $outboxEventId): void
    {
        $outbox = $this->outboxRepository->getById($outboxEventId);

        if (! $outbox || $outbox->status === OutboxStatus::PUBLISHED) {
            return;
        }

        $this->outboxRepository->markProcessing($outbox);

        try {
            event(new OutboxEventPublished($outbox->event_type, (array) $outbox->payload));

            $this->outboxRepository->markPublished($outbox);
        } catch (Throwable $exception) {
            $this->outboxRepository->markRetryAfterFailure($outbox, $exception);

            throw $exception;
        }
    }
}
