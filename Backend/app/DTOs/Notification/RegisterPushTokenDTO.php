<?php

namespace App\DTOs\Notification;

use App\Enums\PushTokenProvider;
use Spatie\LaravelData\Data;

class RegisterPushTokenDTO extends Data
{
    public function __construct(
        public readonly string $token,
        public readonly PushTokenProvider $provider = PushTokenProvider::EXPO,
        public readonly ?string $platform = null,
    ) {}
}
