<?php

namespace App\DTOs\RestaurantChain;

use Spatie\LaravelData\Data;

class SearchRestaurantChainsDTO extends Data
{
    public function __construct(
        public readonly ?string $q = null,
        public readonly ?int $pageNumber = null,
        public readonly ?int $pageSize = null,
    ) {
    }
}
