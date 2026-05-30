<?php

namespace App\DTOs\Order;

use Spatie\LaravelData\Data;

class CheckoutPreviewDTO extends Data
{
    /**
     * @param array<int, CheckoutPreviewDiscountDTO> $discounts
     */
    public function __construct(
        public readonly float $subtotal,
        public readonly float $delivery_fee,
        public readonly float $discount_total,
        public readonly float $total,
        public readonly array $discounts,
        public readonly bool $coupon_valid,
        public readonly ?string $coupon_error,
    ) {
    }

    public static function empty(): self
    {
        return new self(
            subtotal: 0.0,
            delivery_fee: 0.0,
            discount_total: 0.0,
            total: 0.0,
            discounts: [],
            coupon_valid: false,
            coupon_error: null,
        );
    }

    public static function fromPricing(array $pricing, ?string $couponCode, ?string $couponError): self
    {
        return new self(
            subtotal: (float) $pricing['subtotal'],
            delivery_fee: (float) $pricing['delivery_fee'],
            discount_total: (float) $pricing['discount_total'],
            total: (float) $pricing['total'],
            discounts: array_map(
                static fn (array $discount): CheckoutPreviewDiscountDTO => CheckoutPreviewDiscountDTO::fromPricingDiscount($discount),
                $pricing['discounts'],
            ),
            coupon_valid: $couponCode !== null && $couponError === null && $pricing['coupon'] !== null,
            coupon_error: $couponError,
        );
    }
}
