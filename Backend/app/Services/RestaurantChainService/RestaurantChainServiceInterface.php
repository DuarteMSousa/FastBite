<?php

namespace App\Services\RestaurantChainService;

use App\DTOs\RestaurantChain\CreateRestaurantChainDTO;
use App\DTOs\RestaurantChain\UpdateRestaurantChainDTO;
use App\Models\RestaurantChain;

interface RestaurantChainServiceInterface
{
    public function getRestaurantChainById(string $id): ?RestaurantChain;

    public function getAllRestaurantChains(int $limit = 100);

    public function createRestaurantChain(CreateRestaurantChainDTO $data): RestaurantChain;

    public function updateRestaurantChain(string $id, UpdateRestaurantChainDTO $data): ?RestaurantChain;

    public function deleteRestaurantChain(string $id): bool;
}
