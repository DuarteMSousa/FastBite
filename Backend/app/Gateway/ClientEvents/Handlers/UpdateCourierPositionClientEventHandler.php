<?php

namespace App\Gateway\ClientEvents\Handlers;

use App\Aspects\ErrorLogged;
use App\Aspects\Logged;
use App\DTOs\Tracking\UpdateCourierLocationDTO;
use App\Gateway\ClientEvents\ClientEventHandler;
use App\Gateway\ClientEvents\ClientSocketMessage;
use App\Gateway\ClientEvents\ReadsClientPayload;
use App\Gateway\Responses\CourierPositionAckResponse;
use App\Gateway\SocketMessage;
use App\Gateway\SocketClientEventType;
use App\Services\TrackingService\TrackingServiceInterface;
use GatewayWorker\Lib\Gateway;

class UpdateCourierPositionClientEventHandler implements ClientEventHandler
{
    use ReadsClientPayload;

    public function __construct(private TrackingServiceInterface $trackingService) {}

    public function type(): SocketClientEventType
    {
        return SocketClientEventType::COURIER_POSITION_SET;
    }

    #[Logged]
    #[ErrorLogged]
    public function handle(string $clientId, ClientSocketMessage $message): void
    {
        $courierId = $this->requiredStringFromMessageOrSession($message, $clientId, 'courier_id', 'courier_id');
        $data = UpdateCourierLocationDTO::from([
            'courier_id' => $courierId,
            'delivery_id' => $message->requiredString('delivery_id'),
            'latitude' => $message->requiredFloat('latitude'),
            'longitude' => $message->requiredFloat('longitude'),
            'recorded_at' => $message->string('recorded_at'),
        ]);

        $result = $this->trackingService->updateCourierLocation($data);

        Gateway::sendToClient($clientId, SocketMessage::response(
            new CourierPositionAckResponse($result['delivery_id'], $result['recorded_at'])
        ));
    }
}
