<?php

namespace App\Services\OrderService;

use App\Aspects\Transactional;
use App\Domain\Geo\GeoMath;
use App\Domain\StateMachines\Orders\OrderStateFactory;
use App\DTOs\Order\CheckoutDTO;
use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\OrderAddress\CreateOrderAddressDTO;
use App\DTOs\Order\OrderItem\CreateOrderItemDTO;
use App\DTOs\Order\OrderItemOption\CreateOrderItemOptionDTO;
use App\Enums\OrderEventType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentEventType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\NotificationEventRecorded;
use App\Jobs\AssignCourierToDeliveryJob;
use App\Jobs\ExpirePendingPaymentJob;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\UserAddress;
use App\Repositories\CartRepository\CartRepositoryInterface;
use App\Repositories\OrderRepository\OrderRepositoryInterface;
use App\Repositories\PaymentRepository\PaymentRepositoryInterface as PaymentRepositoryContract;
use App\Repositories\UserAddressRepository\UserAddressRepositoryInterface;
use App\Services\CartService\CartServiceInterface;
use App\Services\DeliveryService\DeliveryServiceInterface;
use App\Services\OrderPricingService;
use App\Services\OutboxService;
use App\Services\PaymentService\PaymentServiceInterface;
use BackedEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\DataCollection;

class OrderService implements OrderServiceInterface
{
    private array $with = [
        'items.options',
        'address',
        'events',
        'discounts',
        'payment.events',
        'delivery.events',
    ];

    private OrderRepositoryInterface $orders;

    private CartRepositoryInterface $carts;

    private PaymentRepositoryContract $payments;

    private UserAddressRepositoryInterface $addresses;

    public function __construct(
        ?OrderRepositoryInterface $orders = null,
        ?CartRepositoryInterface $carts = null,
        ?PaymentRepositoryContract $payments = null,
        ?UserAddressRepositoryInterface $addresses = null,
    ) {
        $this->orders = $orders ?? app(OrderRepositoryInterface::class);
        $this->carts = $carts ?? app(CartRepositoryInterface::class);
        $this->payments = $payments ?? app(PaymentRepositoryContract::class);
        $this->addresses = $addresses ?? app(UserAddressRepositoryInterface::class);
    }

    public function getClientOrders(string $userId, ?array $statuses = null, int $page = 1, int $perPage = 20)
    {
        return $this->orders->findByUserIdFiltered($userId, $statuses, $page, $perPage)->items();
    }

    public function getClientOrder(string $userId, string $orderId): ?Order
    {
        return $this->orders->findByUserIdAndId($userId, $orderId);
    }

    public function getRestaurantOrders(string $restaurantId, ?array $statuses = null, int $page = 1, int $perPage = 20)
    {
        return $this->orders->findByRestaurantIdFiltered($restaurantId, $statuses, $page, $perPage)->items();
    }

    public function getActiveRestaurantOrders(string $restaurantId)
    {
        return $this->orders->findActiveByRestaurantId($restaurantId);
    }

    public function getRestaurantOrder(string $restaurantId, string $orderId): ?Order
    {
        return $this->orders->findByRestaurantIdAndId($restaurantId, $orderId);
    }

    public function getOrderEvents(string $orderId)
    {
        return $this->orders->getEvents($orderId);
    }

    #[Transactional]
    public function checkoutOrder(string $clientUserId, CheckoutDTO $data): array
    {
        $cart = $this->carts->findCheckoutCart($clientUserId, $data->cart_id);

        if ($cart->items->isEmpty()) {
            throw new \RuntimeException('Cart is empty.');
        }

        $firstRestaurantProduct = $cart->items->first()->restaurantProduct;
        $restaurant = $firstRestaurantProduct->restaurant()->firstOrFail();
        $address = $this->validatedCheckoutAddress($clientUserId, $data->address_id);
        $this->validateCheckoutCart($cart, $restaurant->id, $address);

        $pricing = app(OrderPricingService::class)->price($cart, $restaurant, $address, $data->coupon_code);
        $method = $data->payment_method;
        $paymentStatus = $method === PaymentMethod::CASH ? PaymentStatus::COMPLETED : PaymentStatus::PENDING;
        $orderStatus = $paymentStatus === PaymentStatus::COMPLETED ? OrderStatus::CONFIRMED : OrderStatus::PENDING;

        $order = $this->orders->createOrder(
            $this->checkoutCreateOrderDTO($clientUserId, $restaurant, $address, $cart, $pricing, $orderStatus)
        );
        $cartItemToOrderItem = $this->mapCartItemsToOrderItems($cart, $order);

        foreach ($pricing['discounts'] as $discount) {
            $this->orders->addDiscount($order, [
                'name_snapshot' => $discount['name_snapshot'],
                'description_snapshot' => $discount['description_snapshot'] ?? null,
                'discount_amount' => $discount['discount_amount'],
                'discount_type' => $discount['discount_type'],
                'discount_target' => $discount['discount_target'],
                'order_item_id' => isset($discount['cart_item_id'])
                    ? ($cartItemToOrderItem[$discount['cart_item_id']] ?? null)
                    : null,
                'origin_type' => $discount['origin_type'],
                'origin_id' => $discount['origin_id'],
            ]);
        }

        $payment = $this->payments->createForOrder(
            $order->id,
            $method->value,
            $paymentStatus->value,
            $pricing['total'],
            $paymentStatus === PaymentStatus::COMPLETED ? now() : null,
            $paymentStatus === PaymentStatus::PENDING ? now()->addMinutes(10) : null,
        );

        $this->recordEvent($order, OrderEventType::ORDER_CREATED, [
            'paymentStatus' => $paymentStatus->value,
        ]);
        if ($orderStatus === OrderStatus::CONFIRMED) {
            $this->recordEvent($order, OrderEventType::ORDER_PAYMENT_COMPLETED);
            $this->recordEvent($order, OrderEventType::ORDER_CONFIRMED);
        }

        $this->payments->createEvent($payment, new \App\DTOs\Payment\CreatePaymentEventDTO(
            PaymentEventType::PAYMENT_CREATED,
            now(),
            [],
        ));

        if ($paymentStatus === PaymentStatus::COMPLETED) {
            $this->payments->createEvent($payment, new \App\DTOs\Payment\CreatePaymentEventDTO(
                PaymentEventType::PAYMENT_COMPLETED,
                now(),
                [],
            ));
        } else {
            ExpirePendingPaymentJob::dispatch($payment->id)
                ->delay($payment->expired_at)
                ->afterCommit();
        }
        $this->carts->clearCart($cart->id);

        return [
            'order' => $order->load($this->with),
            'payment' => $payment->refresh()->load('events'),
        ];
    }

    #[Transactional]
    public function cancelOrderByClient(string $userId, string $orderId, ?string $reason): Order
    {
        $order = $this->orders->findByUserIdAndIdOrFail($userId, $orderId);

        if ($order->status === OrderStatus::CANCELLED) {
            return $order->load($this->with);
        }

        $order = $this->transition($order, OrderStatus::CANCELLED, OrderEventType::ORDER_CANCELLED, ['reason' => $reason]);
        $this->cancelPaymentForOrder($order->id, $reason ?? 'order cancelled by client');

        return $order->refresh()->load($this->with);
    }

    #[Transactional]
    public function cancelOrderBySystem(string $orderId, string $reason): Order
    {
        $order = $this->orders->findByIdOrFail($orderId);

        if (in_array($order->status, [OrderStatus::DELIVERED, OrderStatus::CANCELLED], true)) {
            return $order->load($this->with);
        }

        $order = $this->transition($order, OrderStatus::CANCELLED, OrderEventType::ORDER_CANCELLED, [
            'reason' => $reason,
        ]);
        $this->cancelPaymentForOrder($order->id, $reason);

        return $order->refresh()->load($this->with);
    }

    #[Transactional]
    public function acceptOrderByRestaurant(string $orderId): Order
    {
        $order = $this->orders->findByIdOrFail($orderId);

        $order = $this->transition($order, OrderStatus::PREPARING, OrderEventType::ORDER_PREPARING);
        $order->loadMissing(['restaurant.address', 'address']);
        $deliveryFee = app(OrderPricingService::class)->deliveryFee($order->restaurant, $order->address);
        $delivery = app(DeliveryServiceInterface::class)->createDeliveryForOrder($order->id, $deliveryFee);
        AssignCourierToDeliveryJob::dispatch($delivery->id)->afterCommit();

        return $order;
    }

    #[Transactional]
    public function rejectOrderByRestaurant(string $orderId, ?string $reason): Order
    {
        $order = $this->orders->findByIdOrFail($orderId);

        $this->recordEvent($order, OrderEventType::ORDER_REJECTED, ['reason' => $reason]);
        $order = $this->transition($order, OrderStatus::CANCELLED, OrderEventType::ORDER_CANCELLED, ['reason' => $reason]);
        $this->cancelPaymentForOrder($order->id, $reason ?? 'order rejected by restaurant');

        return $order->refresh()->load($this->with);
    }

    #[Transactional]
    public function startPreparingOrder(string $orderId): Order
    {
        $order = $this->orders->findByIdOrFail($orderId);

        $order = $this->transition($order, OrderStatus::PREPARING, OrderEventType::ORDER_PREPARING);
        $order->loadMissing(['restaurant.address', 'address']);
        $deliveryFee = app(OrderPricingService::class)->deliveryFee($order->restaurant, $order->address);
        $delivery = app(DeliveryServiceInterface::class)->createDeliveryForOrder($order->id, $deliveryFee);
        AssignCourierToDeliveryJob::dispatch($delivery->id)->afterCommit();

        return $order;
    }

    #[Transactional]
    public function updateOrderItemStatus(string $orderItemId, string $status): Order
    {
        $item = $this->orders->updateOrderItemStatus($orderItemId, $status);
        $order = $item->order->refresh()->load('items');

        $statuses = $order->items->pluck('status');
        $isAllReady = true;

        foreach ($statuses as $status) {
            $value = $status instanceof BackedEnum ? $status->value : $status;

            if ($value !== OrderItemStatus::READY->value) {
                 $isAllReady = false;
            }
        }

        if ($isAllReady) {
            return $this->transition($order, OrderStatus::READY, OrderEventType::ORDER_READY);
        }

        return $order->load($this->with);
    }

    #[Transactional]
    public function markOrderReady(string $orderId): Order
    {
        $order = $this->orders->findByIdOrFail($orderId);

        return $this->transition($order, OrderStatus::READY, OrderEventType::ORDER_READY);
    }

    #[Transactional]
    public function repeatClientOrder(string $userId, string $orderId): Cart
    {
        $order = $this->orders->findByUserIdAndIdOrFail($userId, $orderId);
        $cart = app(CartServiceInterface::class)->getCartByUserId($userId);
        $this->carts->clearCart($cart->id);

        foreach ($order->items as $item) {
            $cartItem = $this->carts->createCartItem(
                $cart->id,
                $item->restaurant_product_id,
                (int) $item->quantity,
                (float) $item->unit_price,
                (float) $item->total_price,
            );

            foreach ($item->options as $option) {
                $this->carts->createCartItemOption($cartItem->id, $option->product_option_id, (float) $option->extra_price);
            }
        }

        return app(CartServiceInterface::class)->recalculateCartTotal($cart->id);
    }

    #[Transactional]
    public function markOrderOutForDelivery(Order $order): Order
    {
        return $this->transition($order, OrderStatus::OUT_FOR_DELIVERY, OrderEventType::ORDER_OUT_FOR_DELIVERY);
    }

    #[Transactional]
    public function markOrderDelivered(Order $order): Order
    {
        return $this->transition($order, OrderStatus::DELIVERED, OrderEventType::ORDER_DELIVERED);
    }

    #[Transactional]
    public function confirmOrderAfterPayment(Order $order): Order
    {
        $this->recordEvent($order, OrderEventType::ORDER_PAYMENT_COMPLETED);

        return $this->transition($order, OrderStatus::CONFIRMED, OrderEventType::ORDER_CONFIRMED);
    }

    #[Transactional]
    public function recordCourierAssignedToOrder(Order $order): Order
    {
        $this->recordEvent($order, OrderEventType::ORDER_COURIER_ASSIGNED);

        return $order->refresh()->load($this->with);
    }

    #[Transactional]
    public function recordOrderPickedUp(Order $order): Order
    {
        $this->recordEvent($order, OrderEventType::ORDER_PICKED_UP);

        return $order->refresh()->load($this->with);
    }

    private function transition(Order $order, OrderStatus $status, OrderEventType $eventType, array $payload = []): Order
    {
        $order = $this->orders->findByIdOrFail($order->id, lock: true);
        OrderStateFactory::from($order->status)->transition($order, $status);
        $this->recordEvent($order, $eventType, $payload);

        return $order->refresh()->load($this->with);
    }

    private function validatedCheckoutAddress(string $clientUserId, ?string $addressId): UserAddress
    {
        if ($addressId === null) {
            throw ValidationException::withMessages([
                'address_id' => 'Checkout requires a delivery address.',
            ]);
        }

        return $this->addresses->findByUserIdAndIdOrFail($clientUserId, $addressId);
    }

    private function validateCheckoutCart(Cart $cart, string $restaurantId, UserAddress $address): void
    {
        $cart->loadMissing(['items.restaurantProduct.restaurant.address']);

        foreach ($cart->items as $item) {
            if (! $item->restaurantProduct?->is_available) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart contains an unavailable product.',
                ]);
            }

            if ($item->restaurantProduct?->restaurant_id !== $restaurantId) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart can only contain products from one restaurant.',
                ]);
            }
        }

        $restaurant = $cart->items->first()?->restaurantProduct?->restaurant;
        $restaurantAddress = $restaurant?->address;

        if (! $restaurantAddress) {
            throw ValidationException::withMessages([
                'restaurant_id' => 'Restaurant has no pickup address configured.',
            ]);
        }

        if (! $this->isRestaurantOpenNow($restaurant)) {
            throw ValidationException::withMessages([
                'restaurant_id' => 'Restaurant is closed at this time.',
            ]);
        }

        $distanceKm = GeoMath::distanceKm(
            (float) $restaurantAddress->latitude,
            (float) $restaurantAddress->longitude,
            (float) $address->latitude,
            (float) $address->longitude
        );

        if ($restaurant->delivery_radius !== null && $distanceKm > (float) $restaurant->delivery_radius) {
            throw ValidationException::withMessages([
                'address_id' => 'Delivery address is outside the restaurant delivery radius.',
            ]);
        }
    }

    private function checkoutCreateOrderDTO(
        string $clientUserId,
        Restaurant $restaurant,
        UserAddress $address,
        Cart $cart,
        array $pricing,
        OrderStatus $orderStatus
    ): CreateOrderDTO {
        $items = $cart->items->map(function ($cartItem): CreateOrderItemDTO {
            $options = $cartItem->options->map(fn ($cartOption): CreateOrderItemOptionDTO => new CreateOrderItemOptionDTO(
                product_option_id: $cartOption->product_option_id,
                option_name_snapshot: $cartOption->productOption->name,
                extra_price: (float) $cartOption->extra_price
            ));

            return new CreateOrderItemDTO(
                restaurant_product_id: $cartItem->restaurant_product_id,
                status: OrderItemStatus::PENDING,
                quantity: (int) $cartItem->quantity,
                unit_price: (float) $cartItem->unit_price,
                product_name_snapshot: $cartItem->restaurantProduct->product->name,
                total_price: (float) $cartItem->total_price,
                options: new DataCollection(CreateOrderItemOptionDTO::class, $options)
            );
        });

        return new CreateOrderDTO(
            user_id: $clientUserId,
            restaurant_id: $restaurant->id,
            status: $orderStatus,
            total: (float) $pricing['total'],
            restaurant_name_snapshot: $restaurant->name,
            items: new DataCollection(CreateOrderItemDTO::class, $items),
            address: new CreateOrderAddressDTO(
                street: $address->street,
                city: $address->city,
                postal_code: $address->postal_code,
                country: $address->country,
                latitude: (float) $address->latitude,
                longitude: (float) $address->longitude,
            )
        );
    }

    /**
     * @return array<string, string>
     */
    private function mapCartItemsToOrderItems(Cart $cart, Order $order): array
    {
        $orderItems = $order->items()->oldest()->get()->values();
        $cartItemToOrderItem = [];

        foreach ($cart->items->values() as $index => $cartItem) {
            $orderItem = $orderItems->get($index);

            if ($orderItem) {
                $cartItemToOrderItem[$cartItem->id] = $orderItem->id;
            }
        }

        return $cartItemToOrderItem;
    }

    private function cancelPaymentForOrder(string $orderId, string $reason): void
    {
        $payment = $this->payments->getByOrderId($orderId);

        if (! $payment) {
            return;
        }

        if (in_array($payment->status, [PaymentStatus::FAILED, PaymentStatus::CANCELLED], true)) {
            return;
        }

        app(PaymentServiceInterface::class)->cancelPayment($payment->id, $reason, cascadeToOrder: false);
    }

    private function isRestaurantOpenNow(Restaurant $restaurant): bool
    {
        if (! $restaurant->opening_hours || ! $restaurant->closing_hours) {
            return true;
        }

        try {
            $now = Carbon::now();
            $today = $now->copy()->startOfDay();
            $opening = $today->copy()->setTimeFromTimeString($restaurant->opening_hours);
            $closing = $today->copy()->setTimeFromTimeString($restaurant->closing_hours);
        } catch (\Throwable) {
            return true;
        }

        if ($closing->lessThanOrEqualTo($opening)) {
            return $now->greaterThanOrEqualTo($opening) || $now->lessThan($closing);
        }

        return $now->greaterThanOrEqualTo($opening) && $now->lessThan($closing);
    }

    private function recordEvent(Order $order, OrderEventType $eventType, array $payload = []): void
    {
        $occurredAt = now();
        $eventPayload = [
            'eventId' => (string) Str::uuid(),
            'eventName' => $eventType->value,
            'aggregateType' => 'order',
            'aggregateId' => $order->id,
            'orderId' => $order->id,
            'customerId' => $order->user_id,
            'restaurantId' => $order->restaurant_id,
            'restaurantName' => $order->restaurant_name_snapshot,
            'occurredAt' => $occurredAt->toIso8601String(),
            'data' => $payload,
            'channels' => [
                "customer.{$order->user_id}.orders",
                "restaurant.{$order->restaurant_id}.orders",
                "order.{$order->id}.tracking",
            ],
        ];

        $this->orders->addEvent($order, $eventType->value, $occurredAt, $eventPayload);

        app(OutboxService::class)->enqueue('order', $order->id, $eventType->value, $eventPayload);
        NotificationEventRecorded::dispatch($eventType, $eventPayload);
    }
}
