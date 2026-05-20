<?php

namespace App\Repositories\ProductRepository;

use App\DTOs\Product\CreateProductDTO;
use App\DTOs\Product\UpdateProductDTO;

interface ProductRepositoryInterface
{
    public function findById(string $id);

    public function exists(string $id): bool;

    public function findByCategoryId(string $categoryId);

    public function findOptionGroups(string $productId);

    public function belongsToChain(string $productId, string $chainId): bool;

    public function createProduct(CreateProductDTO $data);

    public function updateProduct(string $id, UpdateProductDTO $data);

    public function deleteProduct(string $id);
}
