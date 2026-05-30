<?php

namespace App\GraphQL\Mutations;

use App\Services\PaymentService\PaymentServiceInterface;

class PaymentMutations
{
    public function __construct(private PaymentServiceInterface $paymentService) {}

    public function confirmPayment($_, array $args)
    {
        return $this->paymentService->confirmPayment($args['payment_id'], $args['transaction_id'] ?? null);
    }
}
