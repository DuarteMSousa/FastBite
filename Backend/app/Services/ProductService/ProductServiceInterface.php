<?php

namespace App\Services\ProductService;

use App\DTOs\Product\CreateProductDTO;
use App\DTOs\Product\UpdateProductDTO;

interface ProductServiceInterface
{
    public function getProductsByCategoryId(string $categoryId);

    public function getProductOptionGroups(string $productId);

    public function createProduct(CreateProductDTO $data);

    public function updateProduct(string $id, UpdateProductDTO $data);

    public function deleteProduct(string $id);
}
