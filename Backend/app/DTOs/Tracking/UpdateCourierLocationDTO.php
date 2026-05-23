<?php

namespace App\DTOs\Tracking;

use Spatie\LaravelData\Data;

class UpdateCourierLocationDTO extends Data
{
    public function __construct(
        public readonly string $courier_id,
        public readonly string $delivery_id,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $recorded_at = null,
    ) {
    }
}
