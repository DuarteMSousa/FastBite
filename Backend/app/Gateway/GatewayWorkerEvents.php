<?php

namespace App\Gateway;

use App\Events\CourierSocketDisconnected;
use App\Gateway\ClientEvents\ClientEventDispatcher;
use App\Gateway\Responses\GatewayReadyResponse;
use GatewayWorker\Lib\Gateway;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Throwable;

class GatewayWorkerEvents
{
    public static function onWorkerStart(mixed $businessWorker = null): void
    {
        if (! function_exists('app')) {
            $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
            $app->make(Kernel::class)->bootstrap();
        }
    }

    public static function onConnect(string $clientId): void
    {
        Gateway::sendToClient($clientId, SocketMessage::response(new GatewayReadyResponse($clientId)));
    }

    public static function onMessage(string $clientId, string $message): void
    {
        try {
            app(ClientEventDispatcher::class)->dispatch($clientId, $message);
        } catch (ValidationException $exception) {
            Gateway::sendToClient($clientId, SocketMessage::error(
                'VALIDATION_ERROR',
                'The socket message is invalid.',
                $exception->errors()
            ));
        } catch (ModelNotFoundException $exception) {
            Gateway::sendToClient($clientId, SocketMessage::error(
                'NOT_FOUND',
                'The requested resource was not found.'
            ));
        } catch (Throwable $exception) {
            report($exception);

            Gateway::sendToClient($clientId, SocketMessage::error(
                'SERVER_ERROR',
                $exception->getMessage()
            ));
        }
    }

    public static function onClose(string $clientId): void
    {
        try {
            $session = Gateway::getSession($clientId);
            $courierId = is_array($session) && isset($session['courier_id']) && $session['courier_id'] !== ''
                ? (string) $session['courier_id']
                : null;

            if (! $courierId) {
                return;
            }

            if (Gateway::isUidOnline($courierId)) {
                return;
            }

            CourierSocketDisconnected::dispatch($courierId);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
