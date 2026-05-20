<?php

namespace App\Gateway\ClientEvents;

use App\Gateway\SocketClientEventType;
use Illuminate\Validation\ValidationException;

final readonly class ClientSocketMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public SocketClientEventType $type,
        public array $payload,
    ) {
    }

    public static function fromJson(string $message): self
    {
        $payload = json_decode($message, true);

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'message' => 'Message must be valid JSON.',
            ]);
        }

        $rawType = $payload['type'] ?? null;

        if (! is_string($rawType) || $rawType === '') {
            throw ValidationException::withMessages([
                'type' => 'Message type is required.',
            ]);
        }

        $type = SocketClientEventType::tryFrom($rawType)
            ?? throw ValidationException::withMessages([
                'type' => 'Unknown message type.',
            ]);

        return new self($type, $payload);
    }

    public function string(string $key): ?string
    {
        if (! isset($this->payload[$key]) || $this->payload[$key] === '') {
            return null;
        }

        return (string) $this->payload[$key];
    }

    public function requiredString(string $key): string
    {
        return $this->string($key)
            ?? throw ValidationException::withMessages([$key => "{$key} is required."]);
    }

    public function nullableFloat(string $key): ?float
    {
        return isset($this->payload[$key]) && is_numeric($this->payload[$key])
            ? (float) $this->payload[$key]
            : null;
    }

    public function requiredFloat(string $key): float
    {
        if (! isset($this->payload[$key]) || ! is_numeric($this->payload[$key])) {
            throw ValidationException::withMessages([$key => "{$key} must be numeric."]);
        }

        return (float) $this->payload[$key];
    }

}
