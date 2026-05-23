<?php

namespace App\DTOs\Campaigns\Promotion;

use App\DTOs\Campaigns\PromotionItem\PromotionItemInputDTO;
use App\Enums\DiscountTarget;
use App\Enums\DiscountType;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CreatePromotionDTO extends Data
{
    public function __construct(
        public readonly string $chain_id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly DiscountType $type,
        public readonly DiscountTarget $target,
        public readonly float $discount,
        #[WithCast(DateTimeInterfaceCast::class, format: ['Y-m-d', 'Y-m-d\TH:i:sP'])]
        public readonly ?Carbon $start_date,
        #[WithCast(DateTimeInterfaceCast::class, format: ['Y-m-d', 'Y-m-d\TH:i:sP'])]
        public readonly ?Carbon $end_date,
        #[DataCollectionOf(PromotionItemInputDTO::class)]
        public readonly ?DataCollection $items = null,
    ) {
    }

}
