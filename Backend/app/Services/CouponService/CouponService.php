<?php

namespace App\Services\CouponService;

use App\Aspects\Transactional;
use App\DTOs\Campaigns\Coupon\CreateCouponDTO;
use App\DTOs\Campaigns\Coupon\UpdateCouponDTO;
use App\Enums\DiscountTarget;
use App\Models\Coupon;
use App\Repositories\CategoryRepository\CategoryRepositoryInterface;
use App\Repositories\CouponRepository\CouponRepositoryInterface;
use App\Repositories\ProductRepository\ProductRepositoryInterface;
use App\Repositories\RestaurantChainRepository\RestaurantChainRepositoryInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class CouponService implements CouponServiceInterface
{
    private CouponRepositoryInterface $coupons;

    private RestaurantChainRepositoryInterface $chains;

    private CategoryRepositoryInterface $categories;

    private ProductRepositoryInterface $products;

    public function __construct(
        ?CouponRepositoryInterface $coupons = null,
        ?RestaurantChainRepositoryInterface $chains = null,
        ?CategoryRepositoryInterface $categories = null,
        ?ProductRepositoryInterface $products = null,
    ) {
        $this->coupons = $coupons ?? app(CouponRepositoryInterface::class);
        $this->chains = $chains ?? app(RestaurantChainRepositoryInterface::class);
        $this->categories = $categories ?? app(CategoryRepositoryInterface::class);
        $this->products = $products ?? app(ProductRepositoryInterface::class);
    }

    public function getCouponsByChainId(string $chainId)
    {
        return $this->coupons->findByChainId($chainId);
    }

    public function getCouponByCode(string $code): ?Coupon
    {
        return $this->coupons->findByCode($code);
    }

    public function getCouponById(string $id): ?Coupon
    {
        return $this->coupons->findById($id);
    }

    #[Transactional]
    public function createCoupon(CreateCouponDTO $data): Coupon
    {
        $items = $data->items?->toArray() ?? [];
        $this->validateCoupon($data->chain_id, $data->target, $data->discount, $items, $data->expiry_date);

        $coupon = $this->coupons->createCoupon($data);
        $this->coupons->replaceItems($coupon->id, $items);

        return $this->coupons->findById($coupon->id);
    }

    #[Transactional]
    public function updateCoupon(string $id, UpdateCouponDTO $data): ?Coupon
    {
        $coupon = $this->coupons->findById($id);

        if (! $coupon) {
            return null;
        }

        $target = $data->target ?? DiscountTarget::from($coupon->target);
        $items = $data->items?->toArray();
        $itemsForValidation = $items ?? (
            in_array($target, [DiscountTarget::ORDER, DiscountTarget::DELIVERY], true)
                ? []
                : $coupon->promotionItems->map(fn ($item) => [
                    'id' => $item->id,
                    'item_id' => $item->item_id,
                ])->all()
        );

        $this->validateCoupon(
            $coupon->chain_id,
            $target,
            $data->discount ?? $coupon->discount,
            $itemsForValidation,
            $data->expiry_date ?? $coupon->expiry_date,
        );

        $this->coupons->updateCoupon($id, $data);

        if (in_array($target, [DiscountTarget::ORDER, DiscountTarget::DELIVERY], true)) {
            $this->coupons->replaceItems($id, []);
        } elseif ($items !== null) {
            $this->coupons->replaceItems($id, $items);
        }

        return $this->coupons->findById($id);
    }

    #[Transactional]
    public function deleteCoupon(string $id): bool
    {
        return $this->coupons->deleteCoupon($id);
    }

    private function validateCoupon(string $chainId, DiscountTarget $target, ?float $discount, array $items, mixed $expiryDate): void
    {
        $errors = [];

        if (! $this->chains->exists($chainId)) {
            $errors['chain_id'][] = 'Restaurant chain does not exist.';
        }

        if ($discount === null || $discount <= 0) {
            $errors['discount'][] = 'Discount must be greater than zero.';
        }

        if ($expiryDate !== null) {
            try {
                $expiresAt = $expiryDate instanceof CarbonInterface
                    ? CarbonImmutable::instance($expiryDate)
                    : CarbonImmutable::parse($expiryDate);

                if ($expiresAt->startOfDay()->lt(now()->startOfDay())) {
                    $errors['expiry_date'][] = 'A validade do cupão não pode ser uma data passada.';
                }
            } catch (\Throwable) {
                $errors['expiry_date'][] = 'Data de validade inválida.';
            }
        }

        if (in_array($target, [DiscountTarget::ORDER, DiscountTarget::DELIVERY], true)) {
            if ($items !== []) {
                $errors['items'][] = 'Coupon items are only allowed for product or category targets.';
            }
        } elseif ($items === []) {
            $errors['items'][] = 'Coupon must include at least one item for product or category targets.';
        }

        foreach ($items as $index => $item) {
            $itemId = $item['item_id'] ?? null;

            if (! $itemId) {
                $errors["items.{$index}.item_id"][] = 'Coupon item must have an item_id.';

                continue;
            }

            if ($target === DiscountTarget::CATEGORY && ! $this->categories->belongsToChain($itemId, $chainId)) {
                $errors["items.{$index}.item_id"][] = 'Category does not belong to coupon chain.';
            }

            if ($target === DiscountTarget::PRODUCT) {
                if (! $this->products->belongsToChain($itemId, $chainId)) {
                    $errors["items.{$index}.item_id"][] = 'Product does not belong to coupon chain.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
