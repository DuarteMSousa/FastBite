<?php

namespace App\Services\RestaurantProductService;

use App\Aspects\Transactional;
use App\DTOs\Product\CreateRestaurantProductDTO;
use App\DTOs\Product\UpdateRestaurantProductDTO;
use App\Models\RestaurantProduct;
use App\Repositories\ProductRepository\ProductRepositoryInterface;
use App\Repositories\RestaurantProductRepository\RestaurantProductRepositoryInterface;
use App\Repositories\RestaurantRepository\RestaurantRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RestaurantProductService implements RestaurantProductServiceInterface
{
    private RestaurantProductRepositoryInterface $restaurantProducts;

    private RestaurantRepositoryInterface $restaurants;

    private ProductRepositoryInterface $products;

    public function __construct(
        ?RestaurantProductRepositoryInterface $restaurantProducts = null,
        ?RestaurantRepositoryInterface $restaurants = null,
        ?ProductRepositoryInterface $products = null,
    ) {
        $this->restaurantProducts = $restaurantProducts ?? app(RestaurantProductRepositoryInterface::class);
        $this->restaurants = $restaurants ?? app(RestaurantRepositoryInterface::class);
        $this->products = $products ?? app(ProductRepositoryInterface::class);
    }

    public function getRestaurantProductById(string $id): ?RestaurantProduct
    {
        return $this->restaurantProducts->findById($id);
    }

    public function getRestaurantProductsByRestaurantId(string $restaurantId)
    {
        return $this->restaurantProducts->findByRestaurantId($restaurantId);
    }

    public function getRestaurantCategoriesByRestaurantId(string $restaurantId)
    {
        return $this->getRestaurantMenu($restaurantId)['categories'];
    }

    public function getRestaurantMenu(string $restaurantId): array
    {
        $restaurant = $this->restaurants->findByIdOrFail($restaurantId);
        $products = $this->getRestaurantProductsByRestaurantId($restaurantId);
        $categories = $this->restaurantProducts->findCategoriesByRestaurantId($restaurantId);

        return [
            'restaurant' => $restaurant,
            'categories' => $categories,
            'products' => $products,
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

        return $this->restaurantProducts->createRestaurantProduct($data);
    }

    #[Transactional]
    public function updateRestaurantProduct(string $id, UpdateRestaurantProductDTO $data): ?RestaurantProduct
    {
        $restaurantProduct = $this->restaurantProducts->findById($id);

        if (! $restaurantProduct) {
            return null;
        }

        $input = array_filter($data->toArray(), static fn ($value) => $value !== null);
        $this->validateInput([...$restaurantProduct->toArray(), ...$input]);
        return $this->restaurantProducts->updateRestaurantProduct($id, $data);
    }

    private function validateInput(array $input): void
    {
        $errors = [];

        if (empty($input['restaurant_id']) || ! $this->restaurants->exists($input['restaurant_id'])) {
            $errors['restaurant_id'][] = 'Restaurant does not exist.';
        }

        if (empty($input['product_id']) || ! $this->products->exists($input['product_id'])) {
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
