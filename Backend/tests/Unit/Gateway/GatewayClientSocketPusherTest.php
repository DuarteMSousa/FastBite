<?php

namespace Tests\Unit\Gateway;

use App\Gateway\GatewayClientSocketPusher;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class GatewayClientSocketPusherTest extends TestCase
{
    public function test_rethrows_socket_transport_failures_after_reporting(): void
    {
        config(['gateway_worker.enabled' => true]);

        $pusher = new GatewayClientSocketPusher;
        $push = new ReflectionMethod($pusher, 'push');
        $push->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('gateway down');

        $push->invoke($pusher, static function (): void {
            throw new RuntimeException('gateway down');
        });
    }
}
