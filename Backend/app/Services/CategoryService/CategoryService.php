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
    private CategoryRepositoryInterface $categoryRepository;

    private RestaurantChainRepositoryInterface $restaurantChainRepository;

    public function __construct(
        ?CategoryRepositoryInterface $categoryRepository = null,
        ?RestaurantChainRepositoryInterface $restaurantChainRepository = null,
    ) {
        $this->categoryRepository = $categoryRepository ?? app(CategoryRepositoryInterface::class);
        $this->restaurantChainRepository = $restaurantChainRepository ?? app(RestaurantChainRepositoryInterface::class);
    }

    public function getCategoriesByChainId(string $chainId)
    {
        return $this->categoryRepository->findByRestaurantChainId($chainId);
    }

    public function getCategoryById(string $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    #[Transactional]
    public function createCategory(CreateCategoryDTO $data): Category
    {
        $this->validateInput($data->toArray());

        return $this->categoryRepository->createCategory($data);
    }

    #[Transactional]
    public function updateCategory(string $id, UpdateCategoryDTO $data): ?Category
    {
        $category = $this->categoryRepository->findById($id);

        if (! $category) {
            return null;
        }

        $input = array_filter($data->toArray(), static fn ($value) => $value !== null);
        $this->validateInput([...$category->toArray(), ...$input], $id);
        return $this->categoryRepository->updateCategory($id, $data);
    }

    #[Transactional]
    public function deleteCategory(string $id): bool
    {
        return $this->categoryRepository->deleteCategory($id);
    }

    private function validateInput(array $input, ?string $ignoreId = null): void
    {
        $errors = [];

        if (empty($input['name'])) {
            $errors['name'][] = 'Category name is required.';
        }

        if (empty($input['chain_id']) || ! $this->restaurantChainRepository->exists($input['chain_id'])) {
            $errors['chain_id'][] = 'Restaurant chain does not exist.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
