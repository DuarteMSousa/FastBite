<?php

namespace App\Domain\StateMachines\Payments;

use App\Enums\PaymentStatus;

class RefundedPaymentState extends AbstractPaymentState
{
    public function status(): PaymentStatus
    {
        return PaymentStatus::REFUNDED;
    }

    protected function allowedTransitions(): array
    {
        return [];
    }
}
