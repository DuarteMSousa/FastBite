<?php

namespace App\Gateway\ClientEvents;

use App\Gateway\SocketClientEventType;

interface ClientEventHandler
{
    public function type(): SocketClientEventType;

    public function handle(string $clientId, ClientSocketMessage $message): void;
}
