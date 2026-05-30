<?php

namespace App\Services\CategoryService;

use App\DTOs\Category\CreateCategoryDTO;
use App\DTOs\Category\UpdateCategoryDTO;
use App\Models\Category;

interface CategoryServiceInterface
{
    public function getCategoriesByChainId(string $chainId);

    public function getCategoryById(string $id): ?Category;

    public function createCategory(CreateCategoryDTO $data): Category;

    public function updateCategory(string $id, UpdateCategoryDTO $data): ?Category;

    public function deleteCategory(string $id): bool;
}
