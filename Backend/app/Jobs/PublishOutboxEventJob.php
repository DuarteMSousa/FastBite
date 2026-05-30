<?php

namespace App\Jobs;

use App\Services\OutboxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishOutboxEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $outboxEventId)
    {
    }

    public function handle(OutboxService $outboxService): void
    {
        $outboxService->publishById($this->outboxEventId);
    }
}
