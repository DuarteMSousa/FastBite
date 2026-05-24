<?php

namespace Tests\Feature\GraphQL;

use App\Models\LocalManager;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantChain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatOperationsMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_restaurant_chat_adds_restaurant_manager_participant(): void
    {
        $customer = User::query()->create([
            'name' => 'Cliente Chat',
            'email' => 'cliente_chat@example.com',
            'password' => 'password123',
        ]);

        $manager = User::query()->create([
            'name' => 'Gestor Chat',
            'email' => 'gestor_chat@example.com',
            'password' => 'password123',
        ]);

        $chain = RestaurantChain::query()->create(['name' => 'FastBite Chat']);
        $restaurant = Restaurant::query()->create([
            'chain_id' => $chain->id,
            'name' => 'Chat Grill',
            'opening_hours' => '09:00',
            'closing_hours' => '23:00',
            'delivery_radius' => 10,
        ]);

        LocalManager::query()->create([
            'user_id' => $manager->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $order = Order::query()->create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'PREPARING',
            'total' => 18.5,
            'restaurant_name_snapshot' => 'Chat Grill',
        ]);

        $mutation = <<<'GRAPHQL'
mutation CreateOrderChat($input: CreateOrderChatInput!) {
  createOrderChat(input: $input) {
    id
    type
    participants {
      user_id
    }
  }
}
GRAPHQL;

        $response = $this
            ->actingAs($customer)
            ->postJson('/graphql', [
                'query' => $mutation,
                'variables' => [
                    'input' => [
                        'order_id' => $order->id,
                        'type' => 'CUSTOMER_RESTAURANT',
                        'participant_user_ids' => [$customer->id],
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.createOrderChat.type', 'CUSTOMER_RESTAURANT');

        $participantIds = collect($response->json('data.createOrderChat.participants'))
            ->pluck('user_id')
            ->all();

        $this->assertContains($customer->id, $participantIds);
        $this->assertContains($manager->id, $participantIds);
    }
}
