<?php

namespace App\Services\CategoryService;

use App\Aspects\Transactional;
use App\DTOs\Category\CreateCategoryDTO;
use App\DTOs\Category\UpdateCategoryDTO;
use App\Models\Category;
use App\Repositories\CategoryRepository\CategoryRepositoryInterface;
use App\Repositories\RestaurantChainRepository\RestaurantChainRepositoryInterface;
use Illuminate\Validation\ValidationException;

class CategoryService implements CategoryServiceInterface
{
    private CategoryRepositoryInterface $categories;

    private RestaurantChainRepositoryInterface $chains;

    public function __construct(
        ?CategoryRepositoryInterface $categories = null,
        ?RestaurantChainRepositoryInterface $chains = null,
    ) {
        $this->categories = $categories ?? app(CategoryRepositoryInterface::class);
        $this->chains = $chains ?? app(RestaurantChainRepositoryInterface::class);
    }

    public function getCategoriesByChainId(string $chainId)
    {
        return $this->categories->findByRestaurantChainId($chainId);
    }

    public function getCategoryById(string $id): ?Category
    {
        return $this->categories->findById($id);
    }

    public function getAllCategories(?string $chainId = null, int $limit = 100)
    {
        return $this->categories->findAll($chainId, $limit);
    }

    #[Transactional]
    public function createCategory(CreateCategoryDTO $data): Category
    {
        $this->validateInput($data->toArray());

        return $this->categories->createCategory($data);
    }

    #[Transactional]
    public function updateCategory(string $id, UpdateCategoryDTO $data): ?Category
    {
        $category = $this->categories->findById($id);

        if (! $category) {
            return null;
        }

        $input = array_filter($data->toArray(), static fn ($value) => $value !== null);
        $this->validateInput([...$category->toArray(), ...$input], $id);
        return $this->categories->updateCategory($id, $data);
    }

    #[Transactional]
    public function deleteCategory(string $id): bool
    {
        return $this->categories->deleteCategory($id);
    }

    private function validateInput(array $input, ?string $ignoreId = null): void
    {
        $errors = [];

        if (empty($input['name'])) {
            $errors['name'][] = 'Category name is required.';
        }

        if (empty($input['chain_id']) || ! $this->chains->exists($input['chain_id'])) {
            $errors['chain_id'][] = 'Restaurant chain does not exist.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
