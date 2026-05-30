<?php

namespace App\Domain\Pricing;

use App\Enums\DiscountType;
use Closure;

final class PricingCalculator
{
    public static function normalizeQuantity(?int $quantity): int
    {
        return max(1, (int) $quantity);
    }

    public static function pipe(mixed $value, callable ...$functions): mixed
    {
        return array_reduce(
            $functions,
            static fn (mixed $carry, callable $function): mixed => $function($carry),
            $value
        );
    }

    /**
     * @param  iterable<int, mixed>  $items
     */
    public static function sumBy(iterable $items, callable $selector): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $total += (float) $selector($item);
        }

        return $total;
    }

    public static function multiplyByQuantity(int $quantity): Closure
    {
        $normalizedQuantity = self::normalizeQuantity($quantity);

        return static fn (float|int|string|null $amount): float => (float) $amount * $normalizedQuantity;
    }

    public static function discountFor(DiscountType $type, float $discount): Closure
    {
        return static fn (float $base): float => self::discountAmount($base, $discount, $type);
    }

    /**
     * @param  iterable<int, float|int|string|null>  $optionExtraPrices
     */
    public static function calculateCartItemTotal(float $unitPrice, iterable $optionExtraPrices, int $quantity): float
    {
        $optionsTotal = self::sumBy($optionExtraPrices, static fn ($price): float => (float) $price);

        return self::pipe(
            $unitPrice + $optionsTotal,
            self::multiplyByQuantity($quantity),
            static fn (float $total): float => round($total, 2),
        );
    }

    /**
     * @param  iterable<int, float|int|string|null>  $lineTotals
     */
    public static function calculateSubtotal(iterable $lineTotals): float
    {
        return round(self::sumBy($lineTotals, static fn ($lineTotal): float => (float) $lineTotal), 2);
    }

    public static function discountAmount(float $base, float $discount, DiscountType $type): float
    {
        if ($base <= 0 || $discount <= 0) {
            return 0.0;
        }

        return round(match ($type) {
            DiscountType::PERCENTAGE => $base * min($discount, 100) / 100,
            DiscountType::FIXED_AMOUNT => min($base, $discount),
        }, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $discounts
     */
    public static function calculateDiscountTotal(array $discounts): float
    {
        return round(self::sumBy(
            $discounts,
            static fn (array $discount): float => (float) ($discount['discount_amount'] ?? 0)
        ), 2);
    }

    public static function calculateTotal(float $subtotal, float $deliveryFee, float $discountTotal): float
    {
        return max(0, round($subtotal + $deliveryFee - $discountTotal, 2));
    }
}
