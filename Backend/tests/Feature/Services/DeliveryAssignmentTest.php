<?php

namespace Tests\Feature\Services;

use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantAddress;
use App\Models\RestaurantChain;
use App\Models\User;
use App\Services\DeliveryService\DeliveryServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_courier_without_known_location_can_receive_offer(): void
    {
        [$delivery, $courierUser] = $this->createPendingDeliveryAndCourier();

        app(DeliveryServiceInterface::class)->assignCourierToDelivery($delivery->id);

        $this->assertDatabaseHas('delivery_offers', [
            'delivery_id' => $delivery->id,
            'courier_id' => $courierUser->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_delivery_stays_pending_when_no_courier_is_available_yet(): void
    {
        [$delivery] = $this->createPendingDeliveryAndCourier(createCourier: false);

        app(DeliveryServiceInterface::class)->assignCourierToDelivery($delivery->id);

        $this->assertDatabaseHas('deliveries', [
            'id' => $delivery->id,
            'courier_id' => null,
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseMissing('orders', [
            'id' => $delivery->order_id,
            'status' => 'CANCELLED',
        ]);
    }

    /**
     * @return array{0: Delivery, 1?: User}
     */
    private function createPendingDeliveryAndCourier(bool $createCourier = true): array
    {
        $customer = User::query()->create([
            'name' => 'Cliente Assignment',
            'email' => 'cliente_assignment@example.com',
            'password' => 'password123',
        ]);

        $chain = RestaurantChain::query()->create(['name' => 'FastBite Assignment']);
        $restaurant = Restaurant::query()->create([
            'chain_id' => $chain->id,
            'name' => 'Assignment Grill',
            'opening_hours' => '09:00',
            'closing_hours' => '23:00',
            'delivery_radius' => 10,
        ]);

        RestaurantAddress::query()->create([
            'restaurant_id' => $restaurant->id,
            'street' => 'Rua de Teste 1',
            'city' => 'Porto',
            'postal_code' => '4000-000',
            'country' => 'Portugal',
            'latitude' => 41.1496,
            'longitude' => -8.6109,
        ]);

        $order = Order::query()->create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'PREPARING',
            'total' => 18.5,
            'restaurant_name_snapshot' => 'Assignment Grill',
        ]);

        $delivery = Delivery::query()->create([
            'order_id' => $order->id,
            'courier_id' => null,
            'status' => 'PENDING',
            'delivery_fee' => 2.5,
        ]);

        if (! $createCourier) {
            return [$delivery];
        }

        $courierUser = User::query()->create([
            'name' => 'Estafeta Assignment',
            'email' => 'estafeta_assignment@example.com',
            'password' => 'password123',
        ]);

        Courier::query()->create([
            'user_id' => $courierUser->id,
            'status' => 'AVAILABLE',
        ]);

        return [$delivery, $courierUser];
    }
}
