<?php

namespace App\Events;

use App\Enums\OutboxEventType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestaurantRatingThresholdReached
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload)
    {
    }

    public function broadcastAs(): string
    {
        return OutboxEventType::RESTAURANT_RATING_THRESHOLD_REACHED->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
