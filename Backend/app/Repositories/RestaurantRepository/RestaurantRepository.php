<?php

namespace App\Repositories\RestaurantRepository;

use App\DTOs\Restaurant\SearchRestaurantsDTO;
use App\DTOs\Restaurant\CreateRestaurantDTO;
use App\DTOs\Restaurant\UpdateRestaurantDTO;
use App\Models\Restaurant;

class RestaurantRepository implements RestaurantRepositoryInterface
{
    public function findById(string $id)
    {
        return Restaurant::with(['chain', 'address'])->find($id);
    }

    public function findByIdOrFail(string $id)
    {
        return Restaurant::with(['chain', 'address'])->findOrFail($id);
    }

    public function exists(string $id): bool
    {
        return Restaurant::whereKey($id)->exists();
    }

    public function findByChainId(string $chainId)
    {
        return Restaurant::with(['chain', 'address'])
            ->where('chain_id', $chainId)
            ->orderBy('name')
            ->get();
    }

    public function searchRestaurants(SearchRestaurantsDTO $filters)
    {
        $query = Restaurant::query()->with(['chain', 'address']);

        $q = $filters->q;
        if ($q !== '') {
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('name', 'like', "%{$q}%")
                    ->orWhereHas('chain', function ($chainQuery) use ($q) {
                        $chainQuery->where('name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('address', function ($addressQuery) use ($q) {
                        $addressQuery->where('city', 'like', "%{$q}%")
                            ->orWhere('street', 'like', "%{$q}%")
                            ->orWhere('country', 'like', "%{$q}%")
                            ->orWhere('postal_code', 'like', "%{$q}%");
                    });
            });
        }

        $restaurantName = $filters->name;
        if ($restaurantName !== '') {
            $query->where('name', 'like', "%{$restaurantName}%");
        }

        $chainName = $filters->chainName;
        if ($chainName !== '') {
            $query->whereHas('chain', function ($chainQuery) use ($chainName) {
                $chainQuery->where('name', 'like', "%{$chainName}%");
            });
        }

        $city = $filters->city;
        if ($city !== '') {
            $query->whereHas('address', function ($addressQuery) use ($city) {
                $addressQuery->where('city', 'like', "%{$city}%");
            });
        }

        $country = $filters->country;
        if ($country !== '') {
            $query->whereHas('address', function ($addressQuery) use ($country) {
                $addressQuery->where('country', 'like', "%{$country}%");
            });
        }

        $postalCode = $filters->postalCode;
        if ($postalCode !== '') {
            $query->whereHas('address', function ($addressQuery) use ($postalCode) {
                $addressQuery->where('postal_code', 'like', "%{$postalCode}%");
            });
        }

        $pageNumber = $filters->pageNumber;
        $pageSize = $filters->pageSize;

        return $query
            ->orderBy('name')
            ->paginate($pageSize, ['*'], 'page', $pageNumber);
    }

    public function createRestaurant(CreateRestaurantDTO $data)
    {
        return Restaurant::create([
            'chain_id' => $data->chain_id,
            'name' => $data->name,
            'opening_hours' => $data->opening_hours,
            'closing_hours' => $data->closing_hours,
            'delivery_radius' => $data->delivery_radius,
        ])->load(['chain', 'address']);
    }

    public function upsertAddress(string $restaurantId, array $payload)
    {
        return \App\Models\RestaurantAddress::updateOrCreate(
            ['restaurant_id' => $restaurantId],
            $payload,
        );
    }

    public function updateRestaurant(string $id, UpdateRestaurantDTO $data)
    {
        $restaurant = Restaurant::find($id);
        if ($restaurant) {
            $restaurant->update(array_filter([
                'chain_id' => $data->chain_id,
                'name' => $data->name,
                'opening_hours' => $data->opening_hours,
                'closing_hours' => $data->closing_hours,
                'delivery_radius' => $data->delivery_radius,
            ], static fn ($value) => $value !== null));
            return $restaurant->load(['chain', 'address']);
        }
        return null;
    }

    public function deleteRestaurant(string $id)
    {
        $restaurant = Restaurant::find($id);
        if ($restaurant) {
            $restaurant->delete();
            return true;
        }
        return false;
    }
}
