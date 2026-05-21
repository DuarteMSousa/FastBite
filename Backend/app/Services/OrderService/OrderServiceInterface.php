<?php

namespace App\Services\OrderService;

use App\DTOs\Order\CheckoutDTO;
use App\Models\Cart;
use App\Models\Order;

interface OrderServiceInterface
{
    public function getClientOrders(string $userId, ?array $statuses = null, int $page = 1, int $perPage = 20);

    public function getClientOrder(string $userId, string $orderId): ?Order;

    public function getRestaurantOrders(string $restaurantId, ?array $statuses = null, int $page = 1, int $perPage = 20);

    public function getActiveRestaurantOrders(string $restaurantId);

    public function getRestaurantOrder(string $restaurantId, string $orderId): ?Order;

    public function getOrderEvents(string $orderId);

    public function checkoutOrder(string $clientUserId, CheckoutDTO $data): array;

    /**
     * @return array<string, mixed>
     */
    public function previewCheckout(string $clientUserId, ?string $cartId, ?string $addressId, ?string $couponCode): array;

    public function cancelOrderByClient(string $userId, string $orderId, ?string $reason): Order;

    public function cancelOrderBySystem(string $orderId, string $reason): Order;

    public function acceptOrderByRestaurant(string $orderId): Order;

    public function rejectOrderByRestaurant(string $orderId, ?string $reason): Order;

    public function startPreparingOrder(string $orderId): Order;

    public function updateOrderItemStatus(string $orderItemId, string $status): Order;

    public function markOrderReady(string $orderId): Order;

    public function repeatClientOrder(string $userId, string $orderId): Cart;

    public function confirmOrderAfterPayment(Order $order): Order;

    public function recordCourierAssignedToOrder(Order $order): Order;

    public function recordOrderPickedUp(Order $order): Order;

    public function markOrderOutForDelivery(Order $order): Order;

    public function markOrderDelivered(Order $order): Order;
}
