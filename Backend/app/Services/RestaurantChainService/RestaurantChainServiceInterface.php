<?php

namespace App\Services\RestaurantChainService;

use App\DTOs\RestaurantChain\CreateRestaurantChainDTO;
use App\DTOs\RestaurantChain\SearchRestaurantChainsDTO;
use App\DTOs\RestaurantChain\UpdateRestaurantChainDTO;
use App\Models\ChainManager;
use App\Models\RestaurantChain;

interface RestaurantChainServiceInterface
{
    public function getRestaurantChainById(string $id): ?RestaurantChain;

    public function searchRestaurantChains(SearchRestaurantChainsDTO $filters);

    public function createRestaurantChain(CreateRestaurantChainDTO $data): RestaurantChain;

    public function updateRestaurantChain(string $id, UpdateRestaurantChainDTO $data): ?RestaurantChain;

    public function deleteRestaurantChain(string $id): bool;

    public function assignChainManager(string $userId, string $chainId): ChainManager;
}
