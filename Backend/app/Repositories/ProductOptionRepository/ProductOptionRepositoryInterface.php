<?php

namespace App\Repositories\ProductOptionRepository;

interface ProductOptionRepositoryInterface
{
    public function findByIds(array $ids);
}
