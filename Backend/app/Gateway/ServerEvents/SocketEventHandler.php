<?php

namespace App\Gateway\ServerEvents;

interface SocketEventHandler
{
    public function supports(object $event): bool;

    public function handle(object $event): void;
}
