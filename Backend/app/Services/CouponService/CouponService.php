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
    private CouponRepositoryInterface $couponRepository;

    private RestaurantChainRepositoryInterface $restaurantChainRepository;

    private CategoryRepositoryInterface $categoryRepository;

    private ProductRepositoryInterface $productRepository;

    public function __construct(
        ?CouponRepositoryInterface $couponRepository = null,
        ?RestaurantChainRepositoryInterface $restaurantChainRepository = null,
        ?CategoryRepositoryInterface $categoryRepository = null,
        ?ProductRepositoryInterface $productRepository = null,
    ) {
        $this->couponRepository = $couponRepository ?? app(CouponRepositoryInterface::class);
        $this->restaurantChainRepository = $restaurantChainRepository ?? app(RestaurantChainRepositoryInterface::class);
        $this->categoryRepository = $categoryRepository ?? app(CategoryRepositoryInterface::class);
        $this->productRepository = $productRepository ?? app(ProductRepositoryInterface::class);
    }

    public function getCouponsByChainId(string $chainId)
    {
        return $this->couponRepository->findByChainId($chainId);
    }

    public function getCouponByCode(string $code): ?Coupon
    {
        return $this->couponRepository->findByCode($code);
    }

    public function getCouponById(string $id): ?Coupon
    {
        return $this->couponRepository->findById($id);
    }

    #[Transactional]
    public function createCoupon(CreateCouponDTO $data): Coupon
    {
        $items = $data->items?->toArray() ?? [];
        $this->validateCoupon($data->chain_id, $data->target, $data->discount, $items, $data->expiry_date);

        $coupon = $this->couponRepository->createCoupon($data);
        $this->couponRepository->replaceItems($coupon->id, $items);

        return $this->couponRepository->findById($coupon->id);
    }

    #[Transactional]
    public function updateCoupon(string $id, UpdateCouponDTO $data): ?Coupon
    {
        $coupon = $this->couponRepository->findById($id);

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

        $this->couponRepository->updateCoupon($id, $data);

        if (in_array($target, [DiscountTarget::ORDER, DiscountTarget::DELIVERY], true)) {
            $this->couponRepository->replaceItems($id, []);
        } elseif ($items !== null) {
            $this->couponRepository->replaceItems($id, $items);
        }

        return $this->couponRepository->findById($id);
    }

    #[Transactional]
    public function deleteCoupon(string $id): bool
    {
        return $this->couponRepository->deleteCoupon($id);
    }

    private function validateCoupon(string $chainId, DiscountTarget $target, ?float $discount, array $items, mixed $expiryDate): void
    {
        $errors = [];

        if (! $this->restaurantChainRepository->exists($chainId)) {
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

            if ($target === DiscountTarget::CATEGORY && ! $this->categoryRepository->belongsToChain($itemId, $chainId)) {
                $errors["items.{$index}.item_id"][] = 'Category does not belong to coupon chain.';
            }

            if ($target === DiscountTarget::PRODUCT) {
                if (! $this->productRepository->belongsToChain($itemId, $chainId)) {
                    $errors["items.{$index}.item_id"][] = 'Product does not belong to coupon chain.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
