<?php

namespace App\Gateway\ClientEvents\Handlers;

use App\Gateway\ClientEvents\ClientEventHandler;
use App\Gateway\ClientEvents\ClientSocketMessage;
use App\Gateway\ClientEvents\ReadsClientPayload;
use App\Gateway\Responses\HelloAckResponse;
use App\Gateway\SocketMessage;
use App\Gateway\SocketClientEventType;
use GatewayWorker\Lib\Gateway;

class HelloClientEventHandler implements ClientEventHandler
{
    use ReadsClientPayload;

    public function type(): SocketClientEventType
    {
        return SocketClientEventType::HELLO;
    }

    public function handle(string $clientId, ClientSocketMessage $message): void
    {
        $userId = $message->requiredString('user_id');
        $courierId = $message->string('courier_id');

        Gateway::bindUid($clientId, $userId);
        Gateway::setSession($clientId, [
            'user_id' => $userId,
            'courier_id' => $courierId,
        ]);

        Gateway::joinGroup($clientId, "user.{$userId}.notifications");

        if ($courierId) {
            Gateway::joinGroup($clientId, "courier.{$courierId}.jobs");
        }

        Gateway::sendToClient($clientId, SocketMessage::response(
            new HelloAckResponse($clientId, $userId, $courierId)
        ));
    }
}
