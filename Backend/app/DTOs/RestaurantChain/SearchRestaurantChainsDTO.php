<?php

namespace App\DTOs\RestaurantChain;

use Spatie\LaravelData\Data;

class SearchRestaurantChainsDTO extends Data
{
    public function __construct(
        public readonly string $q = '',
        public readonly int $pageNumber = 1,
        public readonly int $pageSize = 20,
    ) {
    }
}
