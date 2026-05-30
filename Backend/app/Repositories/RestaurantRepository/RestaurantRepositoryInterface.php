<?php

namespace App\Repositories\RestaurantRepository;

use App\DTOs\Restaurant\SearchRestaurantsDTO;
use App\DTOs\Restaurant\CreateRestaurantDTO;
use App\DTOs\Restaurant\UpdateRestaurantDTO;

interface RestaurantRepositoryInterface
{
    public function findById(string $id);

    public function findByIdOrFail(string $id);

    public function exists(string $id): bool;

    public function findByChainId(string $chainId);

    public function searchRestaurants(SearchRestaurantsDTO $filters);

    public function createRestaurant(CreateRestaurantDTO $data);

    public function upsertAddress(string $restaurantId, array $payload);

    public function updateRating(string $restaurantId, float $ratingSum, int $ratingCount);

    public function updateRestaurant(string $id, UpdateRestaurantDTO $data);
}
