<?php

namespace App\Repositories\RestaurantChainRepository;

use App\DTOs\RestaurantChain\CreateRestaurantChainDTO;
use App\DTOs\RestaurantChain\SearchRestaurantChainsDTO;
use App\DTOs\RestaurantChain\UpdateRestaurantChainDTO;
use App\Models\RestaurantChain;

class RestaurantChainRepository implements RestaurantChainRepositoryInterface
{
    public function findById(string $id)
    {
        return RestaurantChain::find($id);
    }

    public function searchRestaurantChains(SearchRestaurantChainsDTO $filters)
    {
        $query = RestaurantChain::query();

        if ($filters->q !== '') {
            $query->where('name', 'like', "%{$filters->q}%");
        }

        return $query
            ->orderBy('name')
            ->paginate($filters->pageSize, ['*'], 'page', $filters->pageNumber);
    }

    public function exists(string $id): bool
    {
        return RestaurantChain::whereKey($id)->exists();
    }

    public function createRestaurantChain(CreateRestaurantChainDTO $data)
    {
        return RestaurantChain::create($data->toArray());
    }

    public function updateRestaurantChain(string $id, UpdateRestaurantChainDTO $data)
    {
        $restaurantChain = RestaurantChain::find($id);
        if ($restaurantChain) {
            $restaurantChain->update($data->toArray());
            return $restaurantChain;
        }
        return null;
    }

}
