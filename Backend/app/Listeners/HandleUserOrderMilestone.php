<?php

namespace App\Listeners;

use App\DTOs\Campaigns\Coupon\CreateCouponDTO;
use App\Enums\DiscountTarget;
use App\Enums\DiscountType;
use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Events\UserOrderMilestoneReached;
use App\Models\Coupon;
use App\Repositories\OrderRepository\OrderRepositoryInterface;
use App\Services\CouponService\CouponServiceInterface;
use App\Services\NotificationService\NotificationServiceInterface;
use Illuminate\Support\Str;

class HandleUserOrderMilestone
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private CouponServiceInterface $couponService,
        private NotificationServiceInterface $notificationService,
    ) {}

    public function handle(UserOrderMilestoneReached $event): void
    {
        $payload = $event->payload;
        $userId = $payload['userId'];
        $milestone = $payload['milestone'];

        $coupon = $this->createMilestoneCoupon($userId, $milestone);

        $this->notificationService->createAndDispatch(
            userId: $userId,
            type: NotificationType::PROMOTION,
            title: "Parabéns! {$milestone} encomendas entregues!",
            message: "Atingiu {$milestone} encomendas. Use o cupão {$coupon->code} para entrega grátis na próxima encomenda!",
            data: [
                'milestone' => $milestone,
                'coupon_code' => $coupon->code,
            ]
        );
    }

    private function createMilestoneCoupon(string $userId, int $milestone): Coupon
    {
        $lastOrders = $this->orders->findByUserIdWithFilters($userId, 1, [OrderStatus::DELIVERED->value]);
        $lastOrder = $lastOrders->first();

        $chainId = $lastOrder?->restaurant?->chain_id;

        if (! $chainId) {
            $chainId = $lastOrder?->restaurant()->value('chain_id');
        }

        $code = 'MILESTONE' . $milestone . '-' . strtoupper(Str::random(6));

        return $this->couponService->createCoupon(new CreateCouponDTO(
            chain_id: $chainId,
            code: $code,
            description: "Entrega grátis por atingir {$milestone} encomendas",
            type: DiscountType::PERCENTAGE,
            target: DiscountTarget::DELIVERY,
            expiry_date: now()->addDays(30)->toDateTimeString(),
            discount: 100,
        ));
    }
}
