<?php

namespace App\Repositories\OrderRepository;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\UpdateOrderDTO;
use App\Models\Order;

interface OrderRepositoryInterface
{
    public function findById(string $id);

    public function findByIdOrFail(string $id, bool $lock = false): Order;

    public function findByUserIdAndId(string $userId, string $orderId);

    public function findByUserIdAndIdOrFail(string $userId, string $orderId): Order;

    public function findByRestaurantIdAndId(string $restaurantId, string $orderId);

    public function findByUserId(string $userId, int $pageNumber, int $pageSize);

    public function findByUserIdFiltered(string $userId, ?array $statuses, int $pageNumber, int $pageSize);

    public function findByRestaurantId(string $restaurantId, int $pageNumber, int $pageSize);

    public function findByRestaurantIdFiltered(string $restaurantId, ?array $statuses, int $pageNumber, int $pageSize);

    public function findActiveByRestaurantId(string $restaurantId);

    public function findByUserIdWithFilters(string $userId, int $limit, ?array $statuses = null);

    public function getEvents(string $orderId);

    public function findOrderItemOrFail(string $orderItemId);

    public function updateOrderItemStatus(string $orderItemId, string $status);

    public function addDiscount(Order $order, array $discount): void;

    public function addEvent(Order $order, string $eventType, mixed $timestamp, array $payload): void;

    public function createOrder(CreateOrderDTO $data);

    public function updateOrder(string $id, UpdateOrderDTO $data);

    public function deleteOrder(string $id);
}
