<?php

namespace Tests\Unit\Services;

use App\Domain\Pricing\PricingCalculator;
use App\Enums\DiscountType;
use PHPUnit\Framework\TestCase;

class PricingCalculatorTest extends TestCase
{
    public function test_normalizes_invalid_quantities_to_one(): void
    {
        $this->assertSame(1, PricingCalculator::normalizeQuantity(0));
        $this->assertSame(1, PricingCalculator::normalizeQuantity(-4));
        $this->assertSame(1, PricingCalculator::normalizeQuantity(null));
        $this->assertSame(3, PricingCalculator::normalizeQuantity(3));
    }

    public function test_calculates_cart_item_total_with_options_and_quantity(): void
    {
        $total = PricingCalculator::calculateCartItemTotal(
            unitPrice: 8.5,
            optionExtraPrices: [1.25, '0.75'],
            quantity: 2
        );

        $this->assertSame(21.0, $total);
    }

    public function test_calculates_percentage_and_fixed_discounts_with_caps(): void
    {
        $this->assertSame(2.5, PricingCalculator::discountAmount(10, 25, DiscountType::PERCENTAGE));
        $this->assertSame(10.0, PricingCalculator::discountAmount(10, 200, DiscountType::PERCENTAGE));
        $this->assertSame(4.0, PricingCalculator::discountAmount(10, 4, DiscountType::FIXED_AMOUNT));
        $this->assertSame(10.0, PricingCalculator::discountAmount(10, 50, DiscountType::FIXED_AMOUNT));
    }

    public function test_calculates_final_total_without_negative_values(): void
    {
        $this->assertSame(13.5, PricingCalculator::calculateTotal(12, 2.5, 1));
        $this->assertSame(0.0, PricingCalculator::calculateTotal(5, 0, 9));
    }

    public function test_calculates_discount_total_with_selector_pipeline(): void
    {
        $discounts = [
            ['discount_amount' => 1.25],
            ['discount_amount' => '2.75'],
            ['name_snapshot' => 'no discount amount'],
        ];

        $this->assertSame(4.0, PricingCalculator::calculateDiscountTotal($discounts));
    }

    public function test_sum_by_uses_selector_delegate(): void
    {
        $items = [
            ['line_total' => 4.25],
            ['line_total' => '5.75'],
            ['name' => 'without total'],
        ];

        $total = PricingCalculator::sumBy(
            $items,
            static fn (array $item): float => (float) ($item['line_total'] ?? 0)
        );

        $this->assertSame(10.0, $total);
    }

    public function test_pipe_composes_transformations_left_to_right(): void
    {
        $result = PricingCalculator::pipe(
            2,
            static fn (int $value): int => $value + 3,
            static fn (int $value): int => $value * 4,
        );

        $this->assertSame(20, $result);
    }

    public function test_multiply_by_quantity_returns_curried_multiplier(): void
    {
        $multiplyByThree = PricingCalculator::multiplyByQuantity(3);
        $normalizeAndMultiply = PricingCalculator::multiplyByQuantity(0);

        $this->assertSame(12.0, $multiplyByThree(4));
        $this->assertSame(5.0, $normalizeAndMultiply(5));
    }

    public function test_discount_for_returns_partially_applied_discount_delegate(): void
    {
        $tenPercentOff = PricingCalculator::discountFor(DiscountType::PERCENTAGE, 10);
        $fourEurosOff = PricingCalculator::discountFor(DiscountType::FIXED_AMOUNT, 4);

        $this->assertSame(2.0, $tenPercentOff(20));
        $this->assertSame(4.0, $fourEurosOff(20));
    }
}
