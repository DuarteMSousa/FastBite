<?php

namespace App\Gateway\Responses;

use App\Gateway\SocketServerEventType;

interface SocketResponse
{
    public function type(): SocketServerEventType;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
