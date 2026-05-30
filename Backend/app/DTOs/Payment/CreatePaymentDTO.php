<?php

namespace App\DTOs\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Spatie\LaravelData\Data;

class CreatePaymentDTO extends Data
{
    public function __construct(
        public readonly string $order_id,
        public readonly PaymentMethod $method,
        public readonly float $amount,
        public readonly PaymentStatus $status = PaymentStatus::PENDING,
        public readonly mixed $paid_at = null,
        public readonly mixed $expired_at = null,
    ) {
    }
}
