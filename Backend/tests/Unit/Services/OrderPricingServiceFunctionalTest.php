<?php

namespace Tests\Unit\Services;

use App\DTOs\Campaigns\Coupon\CreateCouponDTO;
use App\DTOs\Campaigns\Coupon\UpdateCouponDTO;
use App\DTOs\Campaigns\Promotion\CreatePromotionDTO;
use App\DTOs\Campaigns\Promotion\UpdatePromotionDTO;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Models\Restaurant;
use App\Models\RestaurantProduct;
use App\Repositories\CouponRepository\CouponRepositoryInterface;
use App\Repositories\PromotionRepository\PromotionRepositoryInterface;
use App\Services\OrderPricingService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class OrderPricingServiceFunctionalTest extends TestCase
{
    public function test_prices_category_promotion_through_functional_discount_pipeline(): void
    {
        [$cart, $restaurant] = $this->cartWithProduct(categoryId: 'category-1', productId: 'product-1');
        $promotion = $this->promotion(
            id: 'promotion-1',
            target: 'CATEGORY',
            itemId: 'category-1',
        );

        $pricing = new OrderPricingService(
            new InMemoryPromotionRepository(new Collection([$promotion])),
            new InMemoryCouponRepository
        )->price($cart, $restaurant);

        $this->assertSame(2.0, $pricing['discount_total']);
        $this->assertSame('promotion-1', $pricing['discounts'][0]['origin_id']);
        $this->assertSame('cart-item-1', $pricing['discounts'][0]['cart_item_id']);
    }

    public function test_prices_product_coupon_through_functional_target_selector(): void
    {
        [$cart, $restaurant] = $this->cartWithProduct(categoryId: 'category-1', productId: 'product-1');
        $coupon = $this->coupon(
            id: 'coupon-1',
            target: 'PRODUCT',
            itemId: 'product-1',
        );

        $pricing = new OrderPricingService(
            new InMemoryPromotionRepository,
            new InMemoryCouponRepository(new Collection([$coupon]))
        )->price($cart, $restaurant, null, 'PROD10');

        $this->assertSame(2.0, $pricing['discount_total']);
        $this->assertSame('coupon-1', $pricing['discounts'][0]['origin_id']);
        $this->assertNull($pricing['discounts'][0]['cart_item_id']);
    }

    /**
     * @return array{Cart, Restaurant}
     */
    private function cartWithProduct(string $categoryId, string $productId): array
    {
        $category = new Category(['name' => 'Pizzas']);
        $category->id = $categoryId;

        $product = new Product([
            'category_id' => $categoryId,
            'name' => 'Margherita',
            'price' => 20.0,
        ]);
        $product->id = $productId;
        $product->setRelation('category', $category);

        $restaurantProduct = new RestaurantProduct([
            'restaurant_id' => 'restaurant-1',
            'product_id' => $productId,
            'local_price' => 20.0,
            'is_available' => true,
        ]);
        $restaurantProduct->id = 'restaurant-product-1';
        $restaurantProduct->setRelation('product', $product);

        $cartItem = new CartItem([
            'restaurant_product_id' => 'restaurant-product-1',
            'quantity' => 1,
            'unit_price' => 20.0,
            'total_price' => 20.0,
        ]);
        $cartItem->id = 'cart-item-1';
        $cartItem->setRelation('restaurantProduct', $restaurantProduct);
        $cartItem->setRelation('options', new Collection);

        $cart = new Cart(['user_id' => 'user-1', 'total' => 20.0]);
        $cart->id = 'cart-1';
        $cart->setRelation('items', new Collection([$cartItem]));

        $restaurant = new Restaurant([
            'chain_id' => 'chain-1',
            'name' => 'Urban Grill',
            'opening_hours' => '09:00',
            'closing_hours' => '23:00',
            'delivery_radius' => 10,
        ]);
        $restaurant->id = 'restaurant-1';

        return [$cart, $restaurant];
    }

    private function promotion(string $id, string $target, string $itemId): Promotion
    {
        $promotion = new Promotion([
            'chain_id' => 'chain-1',
            'name' => 'Campaign Deal',
            'description' => null,
            'type' => 'PERCENTAGE',
            'target' => $target,
            'discount' => 10,
        ]);
        $promotion->id = $id;
        $promotion->setRelation('promotionItems', new Collection([
            new PromotionItem(['item_id' => $itemId]),
        ]));

        return $promotion;
    }

    private function coupon(string $id, string $target, string $itemId): Coupon
    {
        $coupon = new Coupon([
            'chain_id' => 'chain-1',
            'code' => 'PROD10',
            'description' => null,
            'type' => 'PERCENTAGE',
            'target' => $target,
            'discount' => 10,
            'expiry_date' => null,
        ]);
        $coupon->id = $id;
        $coupon->setRelation('promotionItems', new Collection([
            new PromotionItem(['item_id' => $itemId]),
        ]));

        return $coupon;
    }
}

final class InMemoryPromotionRepository implements PromotionRepositoryInterface
{
    public function __construct(private Collection $promotions = new Collection) {}

    public function getById(string $id)
    {
        return $this->promotions->firstWhere('id', $id);
    }

    public function getByIdOrFail(string $id)
    {
        return $this->getById($id);
    }

    public function getByChainId(string $chainId)
    {
        return $this->promotions->where('chain_id', $chainId)->values();
    }

    public function getActiveByChainId(string $chainId)
    {
        return $this->getByChainId($chainId);
    }

    public function createPromotion(CreatePromotionDTO $data)
    {
        return null;
    }

    public function updatePromotion(string $id, UpdatePromotionDTO $data)
    {
        return null;
    }

    public function deletePromotion(string $id)
    {
        return false;
    }

    public function replaceItems(string $promotionId, array $items): void {}

    public function categoryBelongsToChain(string $categoryId, string $chainId): bool
    {
        return true;
    }

    public function productBelongsToChain(string $productId, string $chainId): bool
    {
        return true;
    }
}

final class InMemoryCouponRepository implements CouponRepositoryInterface
{
    public function __construct(private Collection $coupons = new Collection) {}

    public function findById(string $id)
    {
        return $this->coupons->firstWhere('id', $id);
    }

    public function findByCode(string $code)
    {
        return $this->coupons->firstWhere('code', $code);
    }

    public function findByChainIdAndCode(string $chainId, string $code)
    {
        return $this->coupons
            ->where('chain_id', $chainId)
            ->firstWhere('code', $code);
    }

    public function findByChainId(string $chainId)
    {
        return $this->coupons->where('chain_id', $chainId)->values();
    }

    public function replaceItems(string $couponId, array $items): void {}

    public function createCoupon(CreateCouponDTO $data)
    {
        return null;
    }

    public function updateCoupon(string $id, UpdateCouponDTO $data)
    {
        return null;
    }

    public function deleteCoupon(string $id)
    {
        return false;
    }
}
