<?php

namespace App\Gateway\ClientEvents\Handlers;

use App\Gateway\ClientEvents\ClientEventHandler;
use App\Gateway\ClientEvents\ClientSocketMessage;
use App\Gateway\ClientEvents\ReadsClientPayload;
use App\Gateway\Responses\CourierStatusAckResponse;
use App\Gateway\SocketMessage;
use App\Gateway\SocketClientEventType;
use App\Services\CourierService\CourierServiceInterface;
use GatewayWorker\Lib\Gateway;

class UpdateCourierStatusClientEventHandler implements ClientEventHandler
{
    use ReadsClientPayload;

    public function __construct(private CourierServiceInterface $courierService) {}

    public function type(): SocketClientEventType
    {
        return SocketClientEventType::COURIER_STATUS_SET;
    }

    public function handle(string $clientId, ClientSocketMessage $message): void
    {
        $courierId = $this->requiredStringFromMessageOrSession($message, $clientId, 'courier_id', 'courier_id');
        $status = $message->requiredString('status');

        $courier = $this->courierService->updateCourierStatus($courierId, $status);

        Gateway::sendToClient($clientId, SocketMessage::response(
            new CourierStatusAckResponse($courier->user_id, $courier->status->value)
        ));
    }
}
