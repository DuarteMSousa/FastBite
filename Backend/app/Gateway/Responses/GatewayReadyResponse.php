<?php

namespace App\Gateway\Responses;

use App\Gateway\SocketServerEventType;

final readonly class GatewayReadyResponse implements SocketResponse
{
    public function __construct(public string $clientId) {}

    public function type(): SocketServerEventType
    {
        return SocketServerEventType::GATEWAY_READY;
    }

    public function payload(): array
    {
        return [
            'client_id' => $this->clientId,
            'message' => 'Send hello, then subscribe to groups or send domain events.',
        ];
    }
}
