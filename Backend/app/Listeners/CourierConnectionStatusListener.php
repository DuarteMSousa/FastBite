<?php

namespace App\Listeners;

use App\Enums\CourierStatus;
use App\Events\CourierLastSocketDisconnected;
use App\Services\CourierService\CourierServiceInterface;

class CourierConnectionStatusListener
{
    public function __construct(private CourierServiceInterface $courierService) {}

    public function handle(CourierLastSocketDisconnected $event): void
    {
        $this->courierService->updateCourierStatus(
            $event->courierId,
            CourierStatus::OFFLINE->value
        );
    }
}
