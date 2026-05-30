<?php

namespace App\Repositories\RestaurantChainRepository;

use App\DTOs\RestaurantChain\CreateRestaurantChainDTO;
use App\DTOs\RestaurantChain\SearchRestaurantChainsDTO;
use App\DTOs\RestaurantChain\UpdateRestaurantChainDTO;

interface RestaurantChainRepositoryInterface
{
    public function findById(string $id);

    public function searchRestaurantChains(SearchRestaurantChainsDTO $filters);

    public function exists(string $id): bool;

    public function createRestaurantChain(CreateRestaurantChainDTO $data);

    public function updateRestaurantChain(string $id, UpdateRestaurantChainDTO $data);

    public function deleteRestaurantChain(string $id);
}
