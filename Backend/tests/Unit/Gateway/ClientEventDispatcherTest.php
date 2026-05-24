<?php

namespace Tests\Unit\Gateway;

use App\Gateway\ClientEvents\ClientEventDispatcher;
use App\Gateway\ClientEvents\Handlers\SendChatMessageClientEventHandler;
use App\Gateway\SocketClientEventType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ClientEventDispatcherTest extends TestCase
{
    public function test_registers_socket_client_event_handlers(): void
    {
        $dispatcher = new ClientEventDispatcher;
        $reflection = new ReflectionClass($dispatcher);
        $property = $reflection->getProperty('handlersByType');

        $handlers = $property->getValue($dispatcher);

        $this->assertSame(
            SendChatMessageClientEventHandler::class,
            $handlers[SocketClientEventType::CHAT_MESSAGE_SEND->value] ?? null
        );
    }
}
