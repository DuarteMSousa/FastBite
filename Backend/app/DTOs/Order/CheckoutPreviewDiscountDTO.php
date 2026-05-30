<?php

namespace App\DTOs\Order;

use Spatie\LaravelData\Data;

class CheckoutPreviewDiscountDTO extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $amount,
        public readonly string $type,
        public readonly string $target,
        public readonly string $origin_type,
    ) {
    }

    public static function fromPricingDiscount(array $discount): self
    {
        return new self(
            name: $discount['name_snapshot'],
            description: $discount['description_snapshot'] ?? null,
            amount: (float) $discount['discount_amount'],
            type: $discount['discount_type'],
            target: $discount['discount_target'],
            origin_type: $discount['origin_type'],
        );
    }
}
