<?php

namespace App\Jobs;

use App\Enums\OutboxStatus;
use App\Events\OutboxEventPublished;
use App\Models\OutboxEvent;
use App\Repositories\OutboxRepository\OutboxRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PublishOutboxEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $outboxEventId)
    {
    }

    public function handle(): void
    {
        /** @var OutboxEvent|null $outbox */
        $outbox = app(OutboxRepositoryInterface::class)->getById($this->outboxEventId);

        if (! $outbox) {
            return;
        }

        if ($outbox->status === OutboxStatus::PUBLISHED) {
            return;
        }

        $outboxRepository = app(OutboxRepositoryInterface::class);
        $outboxRepository->markProcessing($outbox);

        try {
            $this->publish($outbox->event_type, (array) $outbox->payload);

            $outboxRepository->markPublished($outbox);
        } catch (Throwable $exception) {
            $outboxRepository->markRetryAfterFailure($outbox, $exception);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function publish(string $eventName, array $payload): void
    {
        event(new OutboxEventPublished($eventName, $payload));
    }
}
