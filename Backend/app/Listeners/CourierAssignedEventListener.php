<?php

namespace App\Listeners;

use App\Enums\CourierStatus;
use App\Events\CourierSocketDisconnected;
use App\Services\CourierService\CourierServiceInterface;

class CourierConnectionStatusListener
{
    public function __construct(private CourierServiceInterface $courierService) {}

    public function handle(CourierSocketDisconnected $event): void
    {
        $this->courierService->updateCourierStatus(
            $event->courierId,
            CourierStatus::OFFLINE->value
        );
    }
}
