<?php

namespace App\Repositories\ProductOptionRepository;

use App\Models\ProductOption;

class ProductOptionRepository implements ProductOptionRepositoryInterface
{
    public function findByIds(array $ids)
    {
        return ProductOption::query()->whereIn('id', $ids)->get();
    }
}
