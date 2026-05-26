<?php

namespace App\Domain\StateMachines\Orders;

use App\Enums\OrderStatus;

class CourierAssignedOrderState extends AbstractOrderState
{
    public function status(): OrderStatus
    {
        return OrderStatus::COURIER_ASSIGNED;
    }

    protected function allowedTransitions(): array
    {
        return [OrderStatus::CONFIRMED, OrderStatus::CANCELLED];
    }
}
