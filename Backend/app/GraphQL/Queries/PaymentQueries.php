<?php

namespace App\GraphQL\Queries;

use App\Services\PaymentService\PaymentServiceInterface;

class PaymentQueries
{
    public function __construct(private PaymentServiceInterface $paymentService) {}

    public function getPaymentByOrderId($_, array $args)
    {
        return $this->paymentService->getPaymentByOrderId($args['order_id']);
    }
}
