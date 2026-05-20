<?php

namespace App\GraphQL\Mutations;

use App\DTOs\Category\CreateCategoryDTO;
use App\DTOs\Category\UpdateCategoryDTO;
use App\DTOs\Product\CreateProductDTO;
use App\DTOs\Product\CreateRestaurantProductDTO;
use App\DTOs\Product\UpdateProductDTO;
use App\DTOs\Product\UpdateRestaurantProductDTO;
use App\Services\CategoryService\CategoryServiceInterface;
use App\Services\ProductService\ProductServiceInterface;
use App\Services\RestaurantProductService\RestaurantProductServiceInterface;

class MenuMutations
{
    public function __construct(
        private CategoryServiceInterface $categoryService,
        private ProductServiceInterface $productService,
        private RestaurantProductServiceInterface $restaurantProductService,
    ) {}

    public function createCategory($_, array $args)
    {
        return $this->categoryService->createCategory(CreateCategoryDTO::from($args['input']));
    }

    public function updateCategory($_, array $args)
    {
        return $this->categoryService->updateCategory($args['id'], UpdateCategoryDTO::from($args['input']));
    }

    public function deleteCategory($_, array $args): bool
    {
        return $this->categoryService->deleteCategory($args['id']);
    }

    public function createProduct($_, array $args)
    {
        return $this->productService->createProduct(CreateProductDTO::from($args['input']));
    }

    public function updateProduct($_, array $args)
    {
        return $this->productService->updateProduct($args['id'], UpdateProductDTO::from($args['input']));
    }

    public function deleteProduct($_, array $args): bool
    {
        return $this->productService->deleteProduct($args['id']);
    }

    public function createRestaurantProduct($_, array $args)
    {
        return $this->restaurantProductService->createRestaurantProduct(CreateRestaurantProductDTO::from($args['input']));
    }

    public function updateRestaurantProduct($_, array $args)
    {
        return $this->restaurantProductService->updateRestaurantProduct($args['id'], UpdateRestaurantProductDTO::from($args['input']));
    }

    public function setRestaurantProductAvailability($_, array $args)
    {
        return $this->restaurantProductService->setRestaurantProductAvailability($args['id'], $args['is_available']);
    }
}
