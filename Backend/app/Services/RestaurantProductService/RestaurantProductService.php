<?php

namespace App\Services\RestaurantProductService;

use App\Aspects\Transactional;
use App\DTOs\Product\CreateRestaurantProductDTO;
use App\DTOs\Product\UpdateRestaurantProductDTO;
use App\Models\RestaurantProduct;
use App\Repositories\CategoryRepository\CategoryRepositoryInterface;
use App\Repositories\ProductRepository\ProductRepositoryInterface;
use App\Repositories\RestaurantProductRepository\RestaurantProductRepositoryInterface;
use App\Repositories\RestaurantRepository\RestaurantRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RestaurantProductService implements RestaurantProductServiceInterface
{
    private RestaurantProductRepositoryInterface $restaurantProductRepository;

    private RestaurantRepositoryInterface $restaurantRepository;

    private ProductRepositoryInterface $productRepository;

    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(
        ?RestaurantProductRepositoryInterface $restaurantProductRepository = null,
        ?RestaurantRepositoryInterface $restaurantRepository = null,
        ?ProductRepositoryInterface $productRepository = null,
        ?CategoryRepositoryInterface $categoryRepository = null,
    ) {
        $this->restaurantProductRepository = $restaurantProductRepository ?? app(RestaurantProductRepositoryInterface::class);
        $this->restaurantRepository = $restaurantRepository ?? app(RestaurantRepositoryInterface::class);
        $this->productRepository = $productRepository ?? app(ProductRepositoryInterface::class);
        $this->categoryRepository = $categoryRepository ?? app(CategoryRepositoryInterface::class);
    }

    public function getRestaurantProductsByRestaurantId(string $restaurantId)
    {
        return $this->restaurantProductRepository->findByRestaurantId($restaurantId);
    }

    public function getRestaurantMenu(string $restaurantId): array
    {
        $restaurant = $this->restaurantRepository->findByIdOrFail($restaurantId);
        $productRepository = $this->getRestaurantProductsByRestaurantId($restaurantId);
        $categoryRepository = $this->categoryRepository->findByRestaurantId($restaurantId);

        return [
            'restaurant' => $restaurant,
            'categories' => $categoryRepository,
            'products' => $productRepository,
        ];
    }

    #[Transactional]
    public function setRestaurantProductAvailability(string $id, bool $isAvailable): ?RestaurantProduct
    {
        return $this->updateRestaurantProduct($id, new UpdateRestaurantProductDTO(is_available: $isAvailable));
    }

    #[Transactional]
    public function createRestaurantProduct(CreateRestaurantProductDTO $data): RestaurantProduct
    {
        $this->validateInput($data->toArray());

        return $this->restaurantProductRepository->createRestaurantProduct($data);
    }

    #[Transactional]
    public function updateRestaurantProduct(string $id, UpdateRestaurantProductDTO $data): ?RestaurantProduct
    {
        $restaurantProduct = $this->restaurantProductRepository->findById($id);

        if (! $restaurantProduct) {
            return null;
        }

        $input = array_filter($data->toArray(), static fn ($value) => $value !== null);
        $this->validateInput([...$restaurantProduct->toArray(), ...$input]);
        return $this->restaurantProductRepository->updateRestaurantProduct($id, $data);
    }

    private function validateInput(array $input): void
    {
        $errors = [];

        if (empty($input['restaurant_id']) || ! $this->restaurantRepository->exists($input['restaurant_id'])) {
            $errors['restaurant_id'][] = 'Restaurant does not exist.';
        }

        if (empty($input['product_id']) || ! $this->productRepository->exists($input['product_id'])) {
            $errors['product_id'][] = 'Product does not exist.';
        }

        if (($input['local_price'] ?? 0) < 0) {
            $errors['local_price'][] = 'Local price must be greater than or equal to zero.';
        }

        if (($input['estimated_preparation_time_min'] ?? 0) < 0) {
            $errors['estimated_preparation_time_min'][] = 'Estimated preparation time must be greater than or equal to zero.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
