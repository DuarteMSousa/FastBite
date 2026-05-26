<?php

namespace App\Outbox\EventHandlers;

use App\Events\ChatMessageSent;
use App\Events\OutboxEventPublished;

class ChatMessageSentOutboxEventHandler implements OutboxEventHandler
{
    public function handle(OutboxEventPublished $event): void
    {
        event(new ChatMessageSent($event->payload));
    }
}
