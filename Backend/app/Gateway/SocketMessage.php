<?php

namespace App\Gateway;

use App\Gateway\Responses\GatewayErrorResponse;
use App\Gateway\Responses\SocketResponse;

class SocketMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function json(string|SocketServerEventType $type, array $payload = []): string
    {
        return json_encode([
            'type' => $type instanceof SocketServerEventType ? $type->value : $type,
            ...$payload,
        ], JSON_THROW_ON_ERROR);
    }

    public static function response(SocketResponse $response): string
    {
        return self::json($response->type(), $response->payload());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function event(string $eventName, ?string $channel, array $payload): string
    {
        return self::json($eventName, [
            'eventName' => $eventName,
            'channel' => $channel,
            'payload' => $payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(string $code, string $message, array $errors = []): string
    {
        return self::response(new GatewayErrorResponse($code, $message, $errors));
    }
}
