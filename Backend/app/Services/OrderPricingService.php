<?php

namespace App\Services;

use App\Domain\Geo\GeoMath;
use App\Domain\Pricing\PricingCalculator;
use App\Enums\CampaignMorphType;
use App\Enums\DiscountTarget;
use App\Enums\DiscountType;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\OrderAddress;
use App\Models\Promotion;
use App\Models\Restaurant;
use App\Models\UserAddress;
use App\Repositories\CouponRepository\CouponRepositoryInterface;
use App\Repositories\PromotionRepository\PromotionRepositoryInterface;
use Closure;
use Generator;
use Illuminate\Validation\ValidationException;

class OrderPricingService
{
    private const DELIVERY_BASE_FEE = 1.50;

    private const DELIVERY_FEE_PER_KM = 0.45;

    private const DELIVERY_MAX_FEE = 6.00;

    private PromotionRepositoryInterface $promotions;

    private CouponRepositoryInterface $coupons;

    public function __construct(
        ?PromotionRepositoryInterface $promotions = null,
        ?CouponRepositoryInterface $coupons = null,
    ) {
        $this->promotions = $promotions ?? app(PromotionRepositoryInterface::class);
        $this->coupons = $coupons ?? app(CouponRepositoryInterface::class);
    }

    /**
     * @return array{subtotal: float, discount_total: float, delivery_fee: float, total: float, discounts: array<int, array<string, mixed>>, coupon: Coupon|null}
     */
    public function price(
        Cart $cart,
        Restaurant $restaurant,
        UserAddress|OrderAddress|string|null $address = null,
        ?string $couponCode = null
    ): array {
        if (is_string($address) && $couponCode === null) {
            $couponCode = $address;
            $address = null;
        } elseif (! $address instanceof UserAddress && ! $address instanceof OrderAddress) {
            $address = null;
        }

        $cart->loadMissing(['items.restaurantProduct.product.category', 'items.options']);

        $subtotal = PricingCalculator::calculateSubtotal($cart->items->pluck('total_price'));
        $deliveryFee = $address ? $this->deliveryFee($restaurant, $address) : 0.0;
        $discounts = collect($this->activePromotions((string) $restaurant->chain_id))
            ->flatMap(fn (Promotion $promotion): array => iterator_to_array(
                $this->promotionDiscounts($cart, $promotion, $subtotal, $deliveryFee),
                false
            ))
            ->values()
            ->all();

        $coupon = null;
        if ($couponCode !== null && trim($couponCode) !== '') {
            $coupon = $this->validCoupon((string) $restaurant->chain_id, trim($couponCode), $subtotal);
            $discounts = [
                ...$discounts,
                $this->couponDiscount($cart, $coupon, $subtotal, $deliveryFee),
            ];
        }

        $discountTotal = PricingCalculator::calculateDiscountTotal($discounts);
        $total = PricingCalculator::calculateTotal($subtotal, $deliveryFee, $discountTotal);

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'discounts' => $discounts,
            'coupon' => $coupon,
        ];
    }

    public function deliveryFee(Restaurant $restaurant, UserAddress|OrderAddress $address): float
    {
        $restaurant->loadMissing('address');

        if (! $restaurant->address) {
            throw ValidationException::withMessages([
                'restaurant_id' => 'O restaurante não tem morada de recolha configurada.',
            ]);
        }

        $distanceKm = GeoMath::distanceKm(
            (float) $restaurant->address->latitude,
            (float) $restaurant->address->longitude,
            (float) $address->latitude,
            (float) $address->longitude
        );

        return round(min(
            self::DELIVERY_MAX_FEE,
            self::DELIVERY_BASE_FEE + ($distanceKm * self::DELIVERY_FEE_PER_KM)
        ), 2);
    }

    private function activePromotions(string $chainId)
    {
        return $this->promotions->getActiveByChainId($chainId);
    }

    /**
     * @return Generator<int, array<string, mixed>>
     */
    private function promotionDiscounts(Cart $cart, Promotion $promotion, float $subtotal, float $deliveryFee): Generator
    {
        $type = DiscountType::from($promotion->type);
        $target = DiscountTarget::from($promotion->target);

        if (in_array($target, [DiscountTarget::ORDER, DiscountTarget::DELIVERY], true)) {
            $base = $target === DiscountTarget::ORDER ? $subtotal : $deliveryFee;
            $amount = PricingCalculator::discountAmount($base, (float) $promotion->discount, $type);

            yield $this->campaignDiscountSnapshot(
                name: $promotion->name,
                description: $promotion->description,
                amount: $amount,
                type: $type,
                target: $target,
                originType: CampaignMorphType::PROMOTION,
                originId: $promotion->id,
            );

            return;
        }

        foreach ($promotion->promotionItems as $promotionItem) {
            yield from $cart->items
                ->filter($this->matchesCampaignTarget($target, (string) $promotionItem->item_id))
                ->map(fn ($cartItem): array => $this->campaignDiscountSnapshot(
                    name: $promotion->name,
                    description: $promotion->description,
                    amount: PricingCalculator::discountAmount((float) $cartItem->total_price, (float) $promotion->discount, $type),
                    type: $type,
                    target: $target,
                    originType: CampaignMorphType::PROMOTION,
                    originId: $promotion->id,
                    cartItemId: $cartItem->id,
                ));
        }
    }

    private function validCoupon(string $chainId, string $code, float $subtotal): Coupon
    {
        $coupon = $this->coupons->findByChainIdAndCode($chainId, $code);

        if (! $coupon) {
            throw ValidationException::withMessages(['coupon_code' => 'Coupon not found.']);
        }

        if ($coupon->expiry_date && $coupon->expiry_date->isPast()) {
            throw ValidationException::withMessages(['coupon_code' => 'Coupon expired.']);
        }

        return $coupon;
    }

    /**
     * @return array<string, mixed>
     */
    private function couponDiscount(Cart $cart, Coupon $coupon, float $subtotal, float $deliveryFee): array
    {
        $type = DiscountType::from($coupon->type);
        $target = DiscountTarget::from($coupon->target);
        $base = match ($target) {
            DiscountTarget::ORDER => $subtotal,
            DiscountTarget::DELIVERY => $deliveryFee,
            DiscountTarget::PRODUCT, DiscountTarget::CATEGORY => $this->couponTargetBase($cart, $coupon, $target),
        };

        $amount = PricingCalculator::discountAmount($base, (float) $coupon->discount, $type);

        return $this->campaignDiscountSnapshot(
            name: $coupon->code,
            description: $coupon->description,
            amount: $amount,
            type: $type,
            target: $target,
            originType: CampaignMorphType::COUPON,
            originId: $coupon->id,
        );
    }

    private function couponTargetBase(Cart $cart, Coupon $coupon, DiscountTarget $target): float
    {
        $targetIds = $coupon->promotionItems->pluck('item_id');
        $targetValue = $this->targetValueForCartItem($target);

        return (float) $cart->items
            ->filter(static fn ($cartItem): bool => $targetIds->contains($targetValue($cartItem)))
            ->sum(static fn ($cartItem): float => (float) $cartItem->total_price);
    }

    private function matchesCampaignTarget(DiscountTarget $target, string $itemId): Closure
    {
        $targetValue = $this->targetValueForCartItem($target);

        return static fn ($cartItem): bool => $targetValue($cartItem) === $itemId;
    }

    private function targetValueForCartItem(DiscountTarget $target): Closure
    {
        return static function ($cartItem) use ($target): ?string {
            $product = $cartItem->restaurantProduct->product;

            return match ($target) {
                DiscountTarget::PRODUCT => $product->id,
                DiscountTarget::CATEGORY => $product->category_id,
                default => null,
            };
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function campaignDiscountSnapshot(
        string $name,
        ?string $description,
        float $amount,
        DiscountType $type,
        DiscountTarget $target,
        CampaignMorphType $originType,
        string $originId,
        ?string $cartItemId = null,
    ): array {
        return [
            'name_snapshot' => $name,
            'description_snapshot' => $description,
            'discount_amount' => $amount,
            'discount_type' => $type->value,
            'discount_target' => $target->value,
            'origin_type' => $originType->value,
            'origin_id' => $originId,
            'cart_item_id' => $cartItemId,
        ];
    }
}
