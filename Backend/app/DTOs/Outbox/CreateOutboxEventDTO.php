<?php

namespace App\DTOs\Outbox;

use App\Enums\OutboxAggregateType;
use App\Enums\OutboxEventType;

final readonly class CreateOutboxEventDTO
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public OutboxAggregateType $aggregateType,
        public ?string $aggregateId,
        public OutboxEventType $eventType,
        public array $payload,
    ) {
    }
}
