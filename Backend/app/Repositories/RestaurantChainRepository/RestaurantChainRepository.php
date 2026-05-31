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
        $q = trim((string) ($filters->q ?? ''));
        $pageNumber = max(1, (int) ($filters->pageNumber ?? 1));
        $pageSize = max(1, (int) ($filters->pageSize ?? 20));

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        return $query
            ->orderBy('name')
            ->paginate($pageSize, ['*'], 'page', $pageNumber);
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
