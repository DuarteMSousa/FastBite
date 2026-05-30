<?php

namespace App\Repositories\DeliveryOfferRepository;

use App\DTOs\Delivery\CreateDeliveryOfferDTO;
use App\DTOs\Delivery\UpdateDeliveryOfferDTO;
use App\Enums\DeliveryOfferStatus;
use App\Models\DeliveryOffer;

class DeliveryOfferRepository implements DeliveryOfferRepositoryInterface
{
    public function createOffer(CreateDeliveryOfferDTO $data): DeliveryOffer
    {
        return DeliveryOffer::query()->create([
            'delivery_id' => $data->deliveryId,
            'courier_id' => $data->courierId,
            'status' => $data->status->value,
            'expires_at' => $data->expiresAt,
        ]);
    }

    public function getById(string $offerId): ?DeliveryOffer
    {
        return DeliveryOffer::query()->whereKey($offerId)->first();
    }

    public function getByIdOrFail(string $offerId, bool $lock = false): DeliveryOffer
    {
        $query = DeliveryOffer::query();

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($offerId);
    }

    public function getPendingByIdOrFail(string $offerId): DeliveryOffer
    {
        return DeliveryOffer::query()
            ->whereKey($offerId)
            ->where('status', DeliveryOfferStatus::PENDING->value)
            ->firstOrFail();
    }

    public function getPendingByCourierId(string $courierId)
    {
        return DeliveryOffer::query()
            ->with([
                'delivery.order.user',
                'delivery.order.address',
                'delivery.order.items.options',
                'delivery.order.restaurant.address',
                'delivery.positionHistory',
                'courier.user',
            ])
            ->where('courier_id', $courierId)
            ->where('status', DeliveryOfferStatus::PENDING->value)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->get();
    }

    public function updateOffer(DeliveryOffer $offer, UpdateDeliveryOfferDTO $data): DeliveryOffer
    {
        $offer->update(array_filter([
            'status' => $data->status?->value,
            'accepted_at' => $data->acceptedAt,
            'rejected_at' => $data->rejectedAt,
        ], static fn ($value) => $value !== null));

        return $offer;
    }

    public function expireOtherPendingOffers(string $deliveryId, string $acceptedOfferId): int
    {
        return DeliveryOffer::query()
            ->where('delivery_id', $deliveryId)
            ->where('id', '!=', $acceptedOfferId)
            ->where('status', DeliveryOfferStatus::PENDING->value)
            ->update(['status' => DeliveryOfferStatus::EXPIRED->value]);
    }
}
