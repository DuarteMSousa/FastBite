<?php

namespace App\Repositories\CartRepository;

use App\DTOs\Cart\AddCartItemDTO;
use App\DTOs\Cart\CartItemOptionDTO;
use App\DTOs\Cart\CreateCartDTO;
use App\DTOs\Cart\UpdateCartItemDTO;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemOption;
use App\Models\ProductOption;

class CartRepository implements CartRepositoryInterface
{
    public function findById(string $id)
    {
        return Cart::with(['items.restaurantProduct.product', 'items.options.productOption'])->find($id);
    }

    public function findByUserId(string $userId)
    {
        return Cart::with(['items.restaurantProduct.product', 'items.options.productOption'])
            ->where('user_id', $userId)
            ->first();
    }

    public function findOrCreateByUserId(string $userId)
    {
        return Cart::firstOrCreate(['user_id' => $userId], ['total' => 0])
            ->load(['items.restaurantProduct.product', 'items.options.productOption']);
    }

    public function findByUserIdAndCartId(string $userId, string $cartId)
    {
        return Cart::with(['items.restaurantProduct.product', 'items.options.productOption'])
            ->where('user_id', $userId)
            ->find($cartId);
    }

    public function findCheckoutCart(string $userId, ?string $cartId)
    {
        return Cart::with(['items.restaurantProduct.product.category', 'items.options.productOption'])
            ->where('user_id', $userId)
            ->when($cartId, fn ($query, $id) => $query->whereKey($id))
            ->firstOrFail();
    }

    public function findItemByUserIdOrFail(string $userId, string $cartItemId)
    {
        return CartItem::with(['restaurantProduct.product.optionGroups.options', 'options.productOption'])
            ->whereHas('cart', fn ($query) => $query->where('user_id', $userId))
            ->findOrFail($cartItemId);
    }

    public function replaceCartItemOptions(string $cartItemId, $options): void
    {
        $item = CartItem::findOrFail($cartItemId);
        $item->options()->delete();

        foreach ($options as $option) {
            $item->options()->create([
                'product_option_id' => $option->id,
                'extra_price' => $option->extra_price,
            ]);
        }
    }

    public function createCart(CreateCartDTO $data)
    {
        return Cart::create($data->toArray());
    }

    public function addCartItem(string $cartId, AddCartItemDTO $data, float $unitPrice, float $totalPrice)
    {
        return CartItem::create([
            'cart_id' => $cartId,
            'restaurant_product_id' => $data->restaurant_product_id,
            'quantity' => $data->quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
        ]);
    }

    public function createCartItem(string $cartId, string $restaurantProductId, int $quantity, float $unitPrice, float $totalPrice)
    {
        return CartItem::create([
            'cart_id' => $cartId,
            'restaurant_product_id' => $restaurantProductId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
        ]);
    }

    public function createCartItemOption(string $cartItemId, string $productOptionId, float $extraPrice)
    {
        return CartItemOption::create([
            'cart_item_id' => $cartItemId,
            'product_option_id' => $productOptionId,
            'extra_price' => $extraPrice,
        ]);
    }

    public function updateCartItem(string $cartItemId, UpdateCartItemDTO $data, float $totalPrice)
    {
        $cartItem = CartItem::find($cartItemId);

        if (!$cartItem) {
            return null;
        }

        $cartItem->update([
            'quantity' => $data->quantity,
            'total_price' => $totalPrice,
        ]);

        return $cartItem;
    }

    public function updateCartItemTotals(string $cartItemId, int $quantity, float $totalPrice)
    {
        $cartItem = CartItem::findOrFail($cartItemId);
        $cartItem->update([
            'quantity' => $quantity,
            'total_price' => $totalPrice,
        ]);

        return $cartItem;
    }

    public function clearCart(string $cartId): void
    {
        $cart = Cart::findOrFail($cartId);
        $cart->items()->delete();
        $cart->update(['total' => 0]);
    }

    public function updateTotal(string $cartId, float $total)
    {
        $cart = Cart::with(['items.restaurantProduct.product', 'items.options.productOption'])->findOrFail($cartId);
        $cart->update(['total' => $total]);

        return $cart->refresh()->load(['items.restaurantProduct.product', 'items.options.productOption']);
    }

    public function findProductOptionsByIds(array $ids)
    {
        return ProductOption::whereIn('id', $ids)->get();
    }

    public function addCartItemOption(CartItemOptionDTO $data)
    {
        return CartItemOption::create([
            'cart_item_id' => $data->cart_item_id,
            'product_option_id' => $data->option_id,
        ]);
    }

    public function deleteCartItem(string $cartItemId)
    {
        $cartItem = CartItem::find($cartItemId);

        if (!$cartItem) {
            return false;
        }

        $cartItem->delete();

        return true;
    }

    public function deleteCart(string $id)
    {
        $cart = Cart::find($id);

        if (!$cart) {
            return false;
        }

        $cart->delete();

        return true;
    }
}
