<?php

namespace App\Repositories\CartRepository;

use App\DTOs\Cart\AddCartItemDTO;
use App\DTOs\Cart\CartItemOptionDTO;
use App\DTOs\Cart\CreateCartDTO;
use App\DTOs\Cart\UpdateCartItemDTO;

interface CartRepositoryInterface
{
    public function findById(string $id);

    public function findByUserId(string $userId);

    public function findOrCreateByUserId(string $userId);

    public function findByUserIdAndCartId(string $userId, string $cartId);

    public function findCheckoutCart(string $userId, ?string $cartId);

    public function findItemByUserIdOrFail(string $userId, string $cartItemId);

    public function replaceCartItemOptions(string $cartItemId, $options): void;

    public function createCart(CreateCartDTO $data);

    public function addCartItem(string $cartId, AddCartItemDTO $data, float $unitPrice, float $totalPrice);

    public function createCartItem(string $cartId, string $restaurantProductId, int $quantity, float $unitPrice, float $totalPrice);

    public function createCartItemOption(string $cartItemId, string $productOptionId, float $extraPrice);

    public function updateCartItem(string $cartItemId, UpdateCartItemDTO $data, float $totalPrice);

    public function updateCartItemTotals(string $cartItemId, int $quantity, float $totalPrice);

    public function clearCart(string $cartId): void;

    public function updateTotal(string $cartId, float $total);

    public function findProductOptionsByIds(array $ids);

    public function addCartItemOption(CartItemOptionDTO $data);

    public function deleteCartItem(string $cartItemId);

    public function deleteCart(string $id);
}
