<?php

namespace App\GraphQL\Queries;

use App\Services\CourierService\CourierServiceInterface;
use App\Services\DeliveryService\DeliveryServiceInterface;

class DeliveryQueries
{
    public function __construct(
        private CourierServiceInterface $courierService,
        private DeliveryServiceInterface $deliveryService,
    ) {}

    public function getCourierByUserId($_, array $args)
    {
        return $this->courierService->getCourierByUserId($args['user_id']);
    }

    public function getDeliveriesByCourierId($_, array $args)
    {
        return $this->deliveryService->getDeliveriesByCourierId($args['courier_id'], $args['statuses'] ?? null);
    }

    public function getDeliveryOffersByCourierId($_, array $args)
    {
        return $this->deliveryService->getDeliveryOffersByCourierId($args['courier_id']);
    }
}
