<?php

namespace App\Repositories\TrackingRepository;

interface TrackingRepositoryInterface
{
    public function findOrderForUserTracking(string $userId, string $orderId);

    public function findDeliveryForTracking(string $deliveryId);

    public function findDeliveryForCourierOrFail(string $courierId, string $deliveryId);

    public function findLastPositionForDelivery(string $deliveryId);

    public function createPosition(string $deliveryId, float $latitude, float $longitude, string $timestamp): void;
}
