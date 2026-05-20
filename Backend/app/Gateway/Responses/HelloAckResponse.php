<?php

namespace App\Gateway\Responses;

use App\Gateway\SocketServerEventType;

final readonly class HelloAckResponse implements SocketResponse
{
    public function __construct(
        public string $clientId,
        public string $userId,
        public ?string $courierId,
    ) {
    }

    public function type(): SocketServerEventType
    {
        return SocketServerEventType::HELLO_ACK;
    }

    public function payload(): array
    {
        return [
            'client_id' => $this->clientId,
            'user_id' => $this->userId,
            'courier_id' => $this->courierId,
        ];
    }
}
