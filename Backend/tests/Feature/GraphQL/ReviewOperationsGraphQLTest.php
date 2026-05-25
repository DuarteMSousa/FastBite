<?php

namespace Tests\Feature\GraphQL;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantChain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewOperationsGraphQLTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_review_for_delivered_restaurant_order(): void
    {
        $customer = User::query()->create([
            'name' => 'Cliente Reviews',
            'email' => 'customer_reviews@example.com',
            'password' => 'password123',
        ]);

        $chain = RestaurantChain::query()->create(['name' => 'Review Chain']);
        $restaurant = Restaurant::query()->create([
            'chain_id' => $chain->id,
            'name' => 'Review Grill',
            'opening_hours' => '09:00',
            'closing_hours' => '23:00',
            'delivery_radius' => 10,
        ]);

        Order::query()->create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'DELIVERED',
            'total' => 19.5,
            'restaurant_name_snapshot' => 'Review Grill',
        ]);

        $mutation = <<<'GRAPHQL'
mutation CreateReview($input: CreateReviewInput!) {
  createReview(input: $input) {
    id
    rating
    comment
    target_type
    target_id
  }
}
GRAPHQL;

        $response = $this
            ->actingAs($customer)
            ->postJson('/graphql', [
                'query' => $mutation,
                'variables' => [
                    'input' => [
                        'user_id' => $customer->id,
                        'rating' => 5,
                        'comment' => 'Muito bom.',
                        'target_type' => 'RESTAURANT',
                        'target_id' => $restaurant->id,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.createReview.rating', 5)
            ->assertJsonPath('data.createReview.target_type', 'RESTAURANT')
            ->assertJsonPath('data.createReview.target_id', $restaurant->id);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $customer->id,
            'target_type' => 'RESTAURANT',
            'target_id' => $restaurant->id,
            'rating' => 5,
        ]);
    }
}
