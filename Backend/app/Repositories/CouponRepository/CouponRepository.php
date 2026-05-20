<?php

namespace App\Repositories\CouponRepository;

use App\DTOs\Campaigns\Coupon\CreateCouponDTO;
use App\DTOs\Campaigns\Coupon\UpdateCouponDTO;
use App\Models\Coupon;

class CouponRepository implements CouponRepositoryInterface
{
    public function findById(string $id)
    {
        return Coupon::with('promotionItems')->find($id);
    }

    public function findByCode(string $code)
    {
        return Coupon::with('promotionItems')->where('code', $code)->first();
    }

    public function findByChainIdAndCode(string $chainId, string $code)
    {
        return Coupon::with('promotionItems')
            ->where('chain_id', $chainId)
            ->where('code', $code)
            ->first();
    }

    public function findByChainId(string $chainId)
    {
        return Coupon::with('promotionItems')
            ->where('chain_id', $chainId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function replaceItems(string $couponId, array $items): void
    {
        $coupon = Coupon::with('promotionItems')->findOrFail($couponId);
        $keptIds = [];

        foreach ($items as $item) {
            $payload = ['item_id' => $item['item_id']];

            if (! empty($item['id'])) {
                $promotionItem = $coupon->promotionItems()->whereKey($item['id'])->first();
                $promotionItem = $promotionItem
                    ? tap($promotionItem)->update($payload)
                    : $coupon->promotionItems()->create($payload);
            } else {
                $promotionItem = $coupon->promotionItems()->create($payload);
            }

            $keptIds[] = $promotionItem->id;
        }

        if ($items === []) {
            $coupon->promotionItems()->delete();

            return;
        }

        $coupon->promotionItems()->whereNotIn('id', $keptIds)->delete();
    }

    public function createCoupon(CreateCouponDTO $data)
    {
        return Coupon::create([
            'chain_id' => $data->chain_id,
            'code' => $data->code,
            'description' => $data->description,
            'type' => $data->type->value,
            'target' => $data->target->value,
            'expiry_date' => $data->expiry_date,
            'discount' => $data->discount,
        ])->load('promotionItems');
    }

    public function updateCoupon(string $id, UpdateCouponDTO $data)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return null;
        }

        $coupon->update(array_filter([
            'code' => $data->code,
            'description' => $data->description,
            'type' => $data->type?->value,
            'target' => $data->target?->value,
            'expiry_date' => $data->expiry_date,
            'discount' => $data->discount,
        ], static fn ($value) => $value !== null));

        return $coupon->refresh()->load('promotionItems');
    }

    public function deleteCoupon(string $id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return false;
        }

        $coupon->delete();

        return true;
    }
}
