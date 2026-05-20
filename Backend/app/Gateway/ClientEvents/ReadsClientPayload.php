<?php

namespace App\Gateway\ClientEvents;

use GatewayWorker\Lib\Gateway;
use Illuminate\Validation\ValidationException;

trait ReadsClientPayload
{
    private function sessionString(string $clientId, string $key): ?string
    {
        $session = Gateway::getSession($clientId);

        if (! is_array($session) || ! isset($session[$key]) || $session[$key] === '') {
            return null;
        }

        return (string) $session[$key];
    }

    private function requiredSessionString(string $clientId, string $key): string
    {
        return $this->sessionString($clientId, $key)
            ?? throw ValidationException::withMessages([$key => "{$key} is required in the socket session."]);
    }

    private function stringFromMessageOrSession(ClientSocketMessage $message, string $clientId, string $sessionKey, string $payloadKey): ?string
    {
        return $message->string($payloadKey)
            ?? $this->sessionString($clientId, $sessionKey);
    }

    private function requiredStringFromMessageOrSession(ClientSocketMessage $message, string $clientId, string $sessionKey, string $payloadKey): string
    {
        return $this->stringFromMessageOrSession($message, $clientId, $sessionKey, $payloadKey)
            ?? throw ValidationException::withMessages([
                $payloadKey => "{$payloadKey} or {$sessionKey} is required.",
            ]);
    }
}
