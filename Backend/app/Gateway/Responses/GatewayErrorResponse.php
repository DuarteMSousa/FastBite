<?php

namespace App\Gateway\Responses;

use App\Gateway\SocketServerEventType;

final readonly class GatewayErrorResponse implements SocketResponse
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        public string $code,
        public string $message,
        public array $errors = [],
    ) {
    }

    public function type(): SocketServerEventType
    {
        return SocketServerEventType::GATEWAY_ERROR;
    }

    public function payload(): array
    {
        $payload = [
            'code' => $this->code,
            'message' => $this->message,
        ];

        if ($this->errors !== []) {
            $payload['errors'] = $this->errors;
        }

        return $payload;
    }
}
