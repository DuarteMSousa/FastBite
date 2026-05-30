<?php

namespace App\Services\CartService;

use App\Aspects\Transactional;
use App\Domain\Pricing\PricingCalculator;
use App\DTOs\Cart\AddCartItemDTO;
use App\DTOs\Cart\UpdateCartItemDTO;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\RestaurantProduct;
use App\Repositories\CartRepository\CartRepositoryInterface;
use App\Repositories\ProductOptionRepository\ProductOptionRepositoryInterface;
use App\Repositories\RestaurantProductRepository\RestaurantProductRepositoryInterface;
use Illuminate\Validation\ValidationException;

class CartService implements CartServiceInterface
{
    private CartRepositoryInterface $cartRepository;

    private RestaurantProductRepositoryInterface $restaurantProductRepository;

    private ProductOptionRepositoryInterface $productOptionRepository;

    public function __construct(
        ?CartRepositoryInterface $cartRepository = null,
        ?RestaurantProductRepositoryInterface $restaurantProductRepository = null,
        ?ProductOptionRepositoryInterface $productOptionRepository = null,
    ) {
        $this->cartRepository = $cartRepository ?? app(CartRepositoryInterface::class);
        $this->restaurantProductRepository = $restaurantProductRepository ?? app(RestaurantProductRepositoryInterface::class);
        $this->productOptionRepository = $productOptionRepository ?? app(ProductOptionRepositoryInterface::class);
    }

    public function getCartByUserId(string $userId): Cart
    {
        return $this->cartRepository->findOrCreateByUserId($userId);
    }

    #[Transactional]
    public function addCartItem(string $clientUserId, AddCartItemDTO $data): Cart
    {
        $cart = $this->getCartByUserId($clientUserId);
        $restaurantProduct = $this->restaurantProductRepository->findByIdOrFail($data->restaurant_product_id);

        $optionIds = $data->option_ids;
        $options = $this->productOptionRepository->findByIds($optionIds);
        $this->validateRestaurantProductCanBeAdded($cart, $restaurantProduct);
        $this->validateOptionsForProduct($restaurantProduct, $optionIds, $options);

        $unitPrice = $restaurantProduct->local_price ?? $restaurantProduct->product->price;
        $quantity = PricingCalculator::normalizeQuantity($data->quantity);
        $lineTotal = PricingCalculator::calculateCartItemTotal(
            (float) $unitPrice,
            $options->pluck('extra_price'),
            $quantity
        );

        $item = $this->cartRepository->createCartItem($cart->id, $restaurantProduct->id, $quantity, (float) $unitPrice, $lineTotal);
        $this->cartRepository->replaceCartItemOptions($item->id, $options);

        return $this->recalculateCartTotal($cart->id);
    }

    #[Transactional]
    public function updateCartItem(string $clientUserId, string $cartItemId, UpdateCartItemDTO $data): Cart
    {
        $item = $this->cartRepository->findItemByUserIdOrFail($clientUserId, $cartItemId);

        if ($data->option_ids !== null) {
            $options = $this->productOptionRepository->findByIds($data->option_ids);
            $this->validateOptionsForProduct($item->restaurantProduct, $data->option_ids, $options);
            $this->cartRepository->replaceCartItemOptions($item->id, $options);
        } else {
            $options = $item->options;
        }

        $quantity = PricingCalculator::normalizeQuantity($data->quantity);
        $lineTotal = PricingCalculator::calculateCartItemTotal(
            (float) $item->unit_price,
            $options->pluck('extra_price'),
            $quantity
        );
        $this->cartRepository->updateCartItemTotals($item->id, $quantity, $lineTotal);

        return $this->recalculateCartTotal($item->cart_id);
    }

    #[Transactional]
    public function removeCartItem(string $userId, string $cartItemId): Cart
    {
        $item = $this->cartRepository->findItemByUserIdOrFail($userId, $cartItemId);
        $cartId = $item->cart_id;
        $this->cartRepository->deleteCartItem($item->id);

        return $this->recalculateCartTotal($cartId);
    }

    #[Transactional]
    public function clearCart(string $userId): bool
    {
        $cart = $this->getCartByUserId($userId);
        $this->cartRepository->clearCart($cart->id);

        return true;
    }

    #[Transactional]
    public function recalculateCartTotal(string $cartId): Cart
    {
        $cart = $this->cartRepository->findById($cartId);

        return $this->cartRepository->updateTotal($cartId, PricingCalculator::calculateSubtotal($cart->items->pluck('total_price')));
    }

    private function validateRestaurantProductCanBeAdded(Cart $cart, RestaurantProduct $restaurantProduct): void
    {
        if (! $restaurantProduct->is_available) {
            throw ValidationException::withMessages([
                'restaurant_product_id' => 'Product is not available in this restaurant.',
            ]);
        }

        $cart->loadMissing('items.restaurantProduct');
        $existingRestaurantId = $cart->items
            ->map(fn (CartItem $item) => $item->restaurantProduct?->restaurant_id)
            ->filter()
            ->first();

        if ($existingRestaurantId && $existingRestaurantId !== $restaurantProduct->restaurant_id) {
            throw ValidationException::withMessages([
                'restaurant_product_id' => 'O carrinho só pode conter produtos de um restaurante.',
            ]);
        }
    }

    private function validateOptionsForProduct(RestaurantProduct $restaurantProduct, array $optionIds, $options): void
    {
        $restaurantProduct->loadMissing('product.optionGroups.options');

        $selectedOptionIds = collect($optionIds)->unique()->values();

        if ($options->count() !== $selectedOptionIds->count()) {
            throw ValidationException::withMessages([
                'option_ids' => 'One or more selected options do not exist.',
            ]);
        }

        $validOptionIds = $restaurantProduct->product->optionGroups
            ->flatMap(static fn ($group) => $group->options->pluck('id'))
            ->unique()
            ->values();

        if ($selectedOptionIds->diff($validOptionIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'option_ids' => 'Selected options must belong to the chosen product.',
            ]);
        }

        $optionsByGroup = $options->groupBy('option_group_id');
        $groupLimitError = $restaurantProduct->product->optionGroups
            ->map(static function ($group) use ($optionsByGroup): ?string {
                $selectedCount = $optionsByGroup->get($group->id, collect())->count();

                return match (true) {
                    $selectedCount < $group->min_options => "Select at least {$group->min_options} option(s) for {$group->name}.",
                    $selectedCount > $group->max_options => "Select at most {$group->max_options} option(s) for {$group->name}.",
                    default => null,
                };
            })
            ->filter()
            ->first();

        if ($groupLimitError !== null) {
            throw ValidationException::withMessages([
                'option_ids' => $groupLimitError,
            ]);
        }
    }
}
