<?php

namespace App\Gateway\Responses;

use App\Gateway\SocketServerEventType;

final readonly class SubscriptionAckResponse implements SocketResponse
{
    public function __construct(
        public SocketServerEventType $responseType,
        public string $channel,
    ) {
    }

    public function type(): SocketServerEventType
    {
        return $this->responseType;
    }

    public function payload(): array
    {
        return ['channel' => $this->channel];
    }
}
