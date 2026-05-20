<?php

namespace App\Repositories\CategoryRepository;

use App\DTOs\Category\CreateCategoryDTO;
use App\DTOs\Category\UpdateCategoryDTO;
use App\Models\Category;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function findById(string $id)
    {
        return Category::with('products.optionGroups.options')->find($id);
    }

    public function findByRestaurantChainId(string $restaurantChainId)
    {
        return Category::with('products.optionGroups.options')
            ->where('chain_id', $restaurantChainId)
            ->orderBy('name')
            ->get();
    }

    public function findAll(?string $restaurantChainId = null, int $limit = 100)
    {
        return Category::with('products.optionGroups.options')
            ->when($restaurantChainId !== null, fn ($query) => $query->where('chain_id', $restaurantChainId))
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function exists(string $id): bool
    {
        return Category::whereKey($id)->exists();
    }

    public function belongsToChain(string $id, string $chainId): bool
    {
        return Category::where('chain_id', $chainId)->whereKey($id)->exists();
    }

    public function createCategory(CreateCategoryDTO $data)
    {
        return Category::create($data->toArray())->load('products.optionGroups.options');
    }

    public function updateCategory(string $id, UpdateCategoryDTO $data)
    {
        $category = Category::find($id);
        if ($category) {
            $category->update(array_filter($data->toArray(), static fn ($value) => $value !== null));
            return $category->load('products.optionGroups.options');
        }
        return null;
    }


    public function deleteCategory(string $id)
    {
        $category = Category::find($id);
        if ($category) {
            $category->delete();
            return true;
        }
        return false;
    }
}
