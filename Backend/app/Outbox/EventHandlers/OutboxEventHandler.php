<?php

namespace App\Outbox\EventHandlers;

use App\Events\OutboxEventPublished;

interface OutboxEventHandler
{
    public function handle(OutboxEventPublished $event): void;
}
