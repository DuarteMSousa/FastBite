<?php

namespace App\Repositories\DeliveryOfferRepository;

use App\DTOs\Delivery\CreateDeliveryOfferDTO;
use App\DTOs\Delivery\UpdateDeliveryOfferDTO;
use App\Models\DeliveryOffer;

interface DeliveryOfferRepositoryInterface
{
    public function createOffer(CreateDeliveryOfferDTO $data): DeliveryOffer;

    public function getById(string $offerId): ?DeliveryOffer;

    public function getByIdOrFail(string $offerId, bool $lock = false): DeliveryOffer;

    public function getPendingByIdOrFail(string $offerId): DeliveryOffer;

    public function getPendingByCourierId(string $courierId);

    public function updateOffer(DeliveryOffer $offer, UpdateDeliveryOfferDTO $data): DeliveryOffer;

    public function expireOtherPendingOffers(string $deliveryId, string $acceptedOfferId): int;
}
