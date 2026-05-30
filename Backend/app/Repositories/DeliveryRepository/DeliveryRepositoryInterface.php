<?php

namespace App\Repositories\DeliveryRepository;

use App\DTOs\Delivery\CreateDeliveryEventDTO;
use App\DTOs\Delivery\UpdateDeliveryDTO;
use App\Models\Delivery;

interface DeliveryRepositoryInterface
{
    public function getById(string $id): ?Delivery;

    public function getByOrderId(string $orderId): ?Delivery;

    public function getActiveByCourierId(string $courierId): ?Delivery;

    public function getByCourierId(string $courierId, ?array $statuses);

    public function getAssignmentCandidate(string $id): ?Delivery;

    /**
     * @return array<int, string>
     */
    public function getPendingUnassignedDeliveryIds(int $limit = 25): array;

    public function getByIdOrFail(string $id, bool $lock = false): Delivery;

    public function getByIdAndCourierIdOrFail(string $id, string $courierId, bool $lock = false): Delivery;

    public function getOrCreateByOrderId(string $orderId, float $deliveryFee): Delivery;

    public function createEvent(Delivery $delivery, CreateDeliveryEventDTO $data): void;

    public function updateDelivery(Delivery $delivery, UpdateDeliveryDTO $data): Delivery;
}
