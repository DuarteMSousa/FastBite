<?php

namespace App\Repositories\RestaurantProductRepository;

use App\DTOs\Product\CreateRestaurantProductDTO;
use App\DTOs\Product\UpdateRestaurantProductDTO;
use App\Models\RestaurantProduct;

class RestaurantProductRepository implements RestaurantProductRepositoryInterface
{
    private array $with = ['product.optionGroups.options', 'restaurant'];

    public function findById(string $id)
    {
        return RestaurantProduct::with($this->with)->find($id);
    }

    public function findByIdOrFail(string $id)
    {
        return RestaurantProduct::with(['product.optionGroups.options'])->findOrFail($id);
    }

    public function findByRestaurantId(string $restaurantId)
    {
        return RestaurantProduct::with($this->with)
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at')
            ->get();
    }

    public function createRestaurantProduct(CreateRestaurantProductDTO $data)
    {
        return RestaurantProduct::create([
            'restaurant_id' => $data->restaurant_id,
            'product_id' => $data->product_id,
            'local_price' => $data->local_price,
            'is_available' => $data->is_available,
            'estimated_preparation_time_min' => $data->estimated_preparation_time_min,
        ])->load($this->with);
    }

    public function updateRestaurantProduct(string $id, UpdateRestaurantProductDTO $data)
    {
        $restaurantProduct = RestaurantProduct::find($id);

        if (!$restaurantProduct) {
            return null;
        }

        $restaurantProduct->update([
            'local_price' => $data->local_price,
            'is_available' => $data->is_available,
            'estimated_preparation_time_min' => $data->estimated_preparation_time_min,
        ]);

        return $restaurantProduct->load($this->with);
    }

    public function deleteRestaurantProduct(string $id)
    {
        $restaurantProduct = RestaurantProduct::find($id);

        if (!$restaurantProduct) {
            return false;
        }

        $restaurantProduct->delete();

        return true;
    }
}
