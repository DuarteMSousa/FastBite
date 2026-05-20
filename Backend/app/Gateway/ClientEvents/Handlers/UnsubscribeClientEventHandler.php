<?php

namespace App\Gateway\ClientEvents\Handlers;

use App\Gateway\ClientEvents\ClientEventHandler;
use App\Gateway\ClientEvents\ClientSocketMessage;
use App\Gateway\ClientEvents\ReadsClientPayload;
use App\Gateway\Responses\SubscriptionAckResponse;
use App\Gateway\SocketMessage;
use App\Gateway\SocketClientEventType;
use App\Gateway\SocketServerEventType;
use GatewayWorker\Lib\Gateway;

class UnsubscribeClientEventHandler implements ClientEventHandler
{
    use ReadsClientPayload;

    public function type(): SocketClientEventType
    {
        return SocketClientEventType::UNSUBSCRIBE;
    }

    public function handle(string $clientId, ClientSocketMessage $message): void
    {
        $channel = $message->requiredString('channel');

        Gateway::leaveGroup($clientId, $channel);
        Gateway::sendToClient($clientId, SocketMessage::response(
            new SubscriptionAckResponse(SocketServerEventType::UNSUBSCRIBE_ACK, $channel)
        ));
    }
}
