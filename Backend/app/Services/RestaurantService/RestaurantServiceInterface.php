<?php

namespace App\Services\RestaurantService;

use App\DTOs\Restaurant\CreateRestaurantDTO;
use App\DTOs\Restaurant\SearchRestaurantsDTO;
use App\DTOs\Restaurant\UpdateRestaurantDTO;
use App\Models\ChainManager;
use App\Models\LocalManager;
use App\Models\Restaurant;

interface RestaurantServiceInterface
{
    public function searchRestaurants(SearchRestaurantsDTO $filters);

    public function getRestaurantById(string $id): ?Restaurant;

    public function createRestaurant(string $actorUserId, CreateRestaurantDTO $data): Restaurant;

    public function updateRestaurant(string $actorUserId, string $id, UpdateRestaurantDTO $data): ?Restaurant;

    public function deleteRestaurant(string $actorUserId, string $id): bool;

    public function getRestaurantsByChainId(string $chainId);

    public function getRestaurantByLocalManagerUserId(string $userId): ?Restaurant;

    public function getRestaurantByManagerUserId(string $userId): ?Restaurant;

    public function getRestaurantsByManagerUserId(string $userId);

    public function getRestaurantChainByManagerUserId(string $userId);

    public function assignChainManager(string $userId, string $chainId): ChainManager;

    public function assignLocalManager(string $userId, string $restaurantId): LocalManager;
}
