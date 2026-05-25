<?php

namespace App\Gateway;

use GatewayClient\Gateway;
use Throwable;

class GatewayClientSocketPusher
{
    public function sendToGroup(string $group, string $eventName, array $payload): void
    {
        $this->push(fn () => Gateway::sendToGroup(
            $group,
            SocketMessage::event($eventName, $group, $payload)
        ));
    }

    public function sendToUid(string $uid, string $eventName, array $payload): void
    {
        $this->push(fn () => Gateway::sendToUid(
            $uid,
            SocketMessage::event($eventName, null, $payload)
        ));
    }

    private function push(callable $callback): void
    {
        if (! config('gateway_worker.enabled', true)) {
            return;
        }

        Gateway::$registerAddress = sprintf(
            '%s:%s',
            config('gateway_worker.register_host', '127.0.0.1'),
            config('gateway_worker.register_port', 1236)
        );

        try {
            $callback();
        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }
    }
}
