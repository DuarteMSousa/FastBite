<?php

namespace App\Gateway\Responses;

use App\Gateway\SocketServerEventType;

final readonly class CourierPositionAckResponse implements SocketResponse
{
    public function __construct(
        public string $deliveryId,
        public string $recordedAt,
    ) {
    }

    public function type(): SocketServerEventType
    {
        return SocketServerEventType::COURIER_POSITION_ACK;
    }

    public function payload(): array
    {
        return [
            'accepted' => true,
            'delivery_id' => $this->deliveryId,
            'recorded_at' => $this->recordedAt,
        ];
    }
}
