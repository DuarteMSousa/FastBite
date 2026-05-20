<?php

namespace App\Gateway\Responses;

use App\Gateway\SocketServerEventType;

final readonly class CourierStatusAckResponse implements SocketResponse
{
    public function __construct(
        public string $courierId,
        public string $status,
    ) {
    }

    public function type(): SocketServerEventType
    {
        return SocketServerEventType::COURIER_STATUS_ACK;
    }

    public function payload(): array
    {
        return [
            'courier_id' => $this->courierId,
            'status' => $this->status,
        ];
    }
}
