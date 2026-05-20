<?php

namespace App\Listeners;

use App\Gateway\ServerEvents\SocketMessageDispatcher;

class DispatchSocketMessage
{
    public function __construct(private SocketMessageDispatcher $dispatcher) {}

    public function handle(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
