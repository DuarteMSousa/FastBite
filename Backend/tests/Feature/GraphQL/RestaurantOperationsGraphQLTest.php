<?php

namespace Tests\Feature\GraphQL;

use App\Models\Courier;
use App\Models\Delivery;
use App\Models\LocalManager;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\Restaurant;
use App\Models\RestaurantAddress;
use App\Models\RestaurantChain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RestaurantOperationsGraphQLTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_manager_can_query_active_orders_for_own_restaurant(): void
    {
        $customer = User::query()->create([
            'name' => 'Cliente Query',
            'email' => 'customer_query_active@example.com',
            'password' => 'password123',
        ]);

        $manager = User::query()->create([
            'name' => 'Manager Local',
            'email' => 'manager_query_active@example.com',
            'password' => 'password123',
        ]);

        $chain = RestaurantChain::query()->create(['name' => 'FastBite Chain']);
        $restaurant = Restaurant::query()->create([
            'chain_id' => $chain->id,
            'name' => 'Urban Grill',
            'opening_hours' => '09:00',
            'closing_hours' => '23:00',
            'delivery_radius' => 10,
        ]);

        LocalManager::query()->create([
            'user_id' => $manager->id,
            'restaurant_id' => $restaurant->id,
        ]);

        RestaurantAddress::query()->create([
            'restaurant_id' => $restaurant->id,
            'street' => 'Rua A',
            'city' => 'Porto',
            'postal_code' => '4000-001',
            'country' => 'Portugal',
            'latitude' => 41.1496,
            'longitude' => -8.6109,
        ]);

        $activeOrder = Order::query()->create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'COURIER_ASSIGNED',
            'total' => 15,
            'restaurant_name_snapshot' => 'Urban Grill',
        ]);

        $courierUser = User::query()->create([
            'name' => 'Estafeta Query',
            'email' => 'courier_query_active@example.com',
            'password' => 'password123',
        ]);

        Courier::query()->create([
            'user_id' => $courierUser->id,
            'status' => 'BUSY',
        ]);

        Delivery::query()->create([
            'order_id' => $activeOrder->id,
            'courier_id' => $courierUser->id,
            'status' => 'PENDING',
            'delivery_fee' => 2.5,
        ]);

        Order::query()->create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'DELIVERED',
            'total' => 9,
            'restaurant_name_snapshot' => 'Urban Grill',
        ]);

        $query = <<<'GRAPHQL'
query ActiveOrders {
  getActiveRestaurantOrders(restaurant_id: "%s") {
    id
    restaurant_id
    status
  }
}
GRAPHQL;
        $query = sprintf($query, $restaurant->id);

        $response = $this
            ->actingAs($manager)
            ->postJson('/graphql', ['query' => $query]);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.getActiveRestaurantOrders')
            ->assertJsonPath('data.getActiveRestaurantOrders.0.id', $activeOrder->id)
            ->assertJsonPath('data.getActiveRestaurantOrders.0.status', 'COURIER_ASSIGNED');
    }

    public function test_local_manager_can_accept_and_reject_orders_after_courier_assignment(): void
    {
        Queue::fake();

        $customer = User::query()->create([
            'name' => 'Cliente Manage',
            'email' => 'customer_manage_orders@example.com',
            'password' => 'password123',
        ]);

        $manager = User::query()->create([
            'name' => 'Manager Local',
            'email' => 'manager_manage_orders@example.com',
            'password' => 'password123',
        ]);

        $chain = RestaurantChain::query()->create(['name' => 'FastBite Chain']);
        $restaurant = Restaurant::query()->create([
            'chain_id' => $chain->id,
            'name' => 'Urban Grill',
            'opening_hours' => '09:00',
            'closing_hours' => '23:00',
            'delivery_radius' => 10,
        ]);

        LocalManager::query()->create([
            'user_id' => $manager->id,
            'restaurant_id' => $restaurant->id,
        ]);

        RestaurantAddress::query()->create([
            'restaurant_id' => $restaurant->id,
            'street' => 'Rua A',
            'city' => 'Porto',
            'postal_code' => '4000-001',
            'country' => 'Portugal',
            'latitude' => 41.1496,
            'longitude' => -8.6109,
        ]);

        $orderToAccept = Order::query()->create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'COURIER_ASSIGNED',
            'total' => 15,
            'restaurant_name_snapshot' => 'Urban Grill',
        ]);

        $courierUser = User::query()->create([
            'name' => 'Estafeta Manage',
            'email' => 'courier_manage_orders@example.com',
            'password' => 'password123',
        ]);

        Courier::query()->create([
            'user_id' => $courierUser->id,
            'status' => 'BUSY',
        ]);

        Delivery::query()->create([
            'order_id' => $orderToAccept->id,
            'courier_id' => $courierUser->id,
            'status' => 'PENDING',
            'delivery_fee' => 2.5,
        ]);

        OrderAddress::query()->create([
            'order_id' => $orderToAccept->id,
            'street' => 'Rua B',
            'city' => 'Porto',
            'postal_code' => '4000-002',
            'country' => 'Portugal',
            'latitude' => 41.1500,
            'longitude' => -8.6110,
        ]);

        $orderToReject = Order::query()->create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'COURIER_ASSIGNED',
            'total' => 11,
            'restaurant_name_snapshot' => 'Urban Grill',
        ]);

        Delivery::query()->create([
            'order_id' => $orderToReject->id,
            'courier_id' => $courierUser->id,
            'status' => 'PENDING',
            'delivery_fee' => 2.5,
        ]);

        $acceptMutation = <<<'GRAPHQL'
mutation AcceptOrder($input: RestaurantOrderDecisionInput!) {
  acceptOrderByRestaurant(input: $input) {
    id
    status
  }
}
GRAPHQL;

        $rejectMutation = <<<'GRAPHQL'
mutation RejectOrder($input: RestaurantOrderDecisionInput!) {
  rejectOrderByRestaurant(input: $input) {
    id
    status
  }
}
GRAPHQL;

        $acceptResponse = $this
            ->actingAs($manager)
            ->postJson('/graphql', [
                'query' => $acceptMutation,
                'variables' => ['input' => ['order_id' => $orderToAccept->id]],
            ]);

        $acceptResponse
            ->assertOk()
            ->assertJsonPath('data.acceptOrderByRestaurant.id', $orderToAccept->id)
            ->assertJsonPath('data.acceptOrderByRestaurant.status', 'CONFIRMED');

        $rejectResponse = $this
            ->actingAs($manager)
            ->postJson('/graphql', [
                'query' => $rejectMutation,
                'variables' => ['input' => ['order_id' => $orderToReject->id]],
            ]);

        $rejectResponse
            ->assertOk()
            ->assertJsonPath('data.rejectOrderByRestaurant.id', $orderToReject->id)
            ->assertJsonPath('data.rejectOrderByRestaurant.status', 'CANCELLED');

        $this->assertDatabaseHas('orders', [
            'id' => $orderToAccept->id,
            'status' => 'CONFIRMED',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderToReject->id,
            'status' => 'CANCELLED',
        ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $orderToAccept->id,
            'event_type' => 'ORDER_CONFIRMED',
        ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $orderToReject->id,
            'event_type' => 'ORDER_CANCELLED',
        ]);
    }
}
