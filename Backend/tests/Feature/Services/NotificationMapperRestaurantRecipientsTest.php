<?php

namespace Tests\Feature\Services;

use App\Domain\Notifications\OutboxNotificationMapper;
use App\Enums\OrderEventType;
use App\Models\ChainManager;
use App\Models\LocalManager;
use App\Models\Restaurant;
use App\Models\RestaurantChain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationMapperRestaurantRecipientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_order_notifications_are_created_for_local_and_chain_managers(): void
    {
        $chain = RestaurantChain::query()->create(['name' => 'FastBite']);
        $restaurant = Restaurant::query()->create([
            'chain_id' => $chain->id,
            'name' => 'FastBite Centro',
            'opening_hours' => '10:00',
            'closing_hours' => '23:00',
            'delivery_radius' => 5,
        ]);
        $localManager = User::query()->create([
            'name' => 'Local Manager',
            'email' => 'local@example.test',
            'password' => 'password',
        ]);
        $chainManager = User::query()->create([
            'name' => 'Chain Manager',
            'email' => 'chain@example.test',
            'password' => 'password',
        ]);

        LocalManager::query()->create([
            'user_id' => $localManager->id,
            'restaurant_id' => $restaurant->id,
        ]);
        ChainManager::query()->create([
            'user_id' => $chainManager->id,
            'chain_id' => $chain->id,
        ]);

        $notifications = app(OutboxNotificationMapper::class)->mapAll(OrderEventType::ORDER_CONFIRMED, [
            'eventName' => OrderEventType::ORDER_CONFIRMED->value,
            'orderId' => 'order-1',
            'customerId' => 'customer-1',
            'restaurantId' => $restaurant->id,
            'status' => 'CONFIRMED',
        ]);

        $this->assertContains($localManager->id, array_column($notifications, 'userId'));
        $this->assertContains($chainManager->id, array_column($notifications, 'userId'));
    }

    public function test_restaurant_order_notifications_fall_back_to_chain_manager_without_local_manager(): void
    {
        $chain = RestaurantChain::query()->create(['name' => 'FastBite']);
        $restaurant = Restaurant::query()->create([
            'chain_id' => $chain->id,
            'name' => 'FastBite Gaia',
            'opening_hours' => '11:00',
            'closing_hours' => '22:30',
            'delivery_radius' => 5,
        ]);
        $chainManager = User::query()->create([
            'name' => 'Chain Manager',
            'email' => 'chain-only@example.test',
            'password' => 'password',
        ]);

        ChainManager::query()->create([
            'user_id' => $chainManager->id,
            'chain_id' => $chain->id,
        ]);

        $notifications = app(OutboxNotificationMapper::class)->mapAll(OrderEventType::ORDER_CONFIRMED, [
            'eventName' => OrderEventType::ORDER_CONFIRMED->value,
            'orderId' => 'order-1',
            'customerId' => 'customer-1',
            'restaurantId' => $restaurant->id,
            'status' => 'CONFIRMED',
        ]);

        $this->assertContains($chainManager->id, array_column($notifications, 'userId'));
    }
}
