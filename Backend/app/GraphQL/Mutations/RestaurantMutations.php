<?php

namespace App\GraphQL\Mutations;

use App\DTOs\Restaurant\CreateRestaurantDTO;
use App\DTOs\Restaurant\UpdateRestaurantDTO;
use App\DTOs\RestaurantChain\CreateRestaurantChainDTO;
use App\DTOs\RestaurantChain\UpdateRestaurantChainDTO;
use App\Services\RestaurantChainService\RestaurantChainServiceInterface;
use App\Services\RestaurantService\RestaurantServiceInterface;

class RestaurantMutations
{
    public function __construct(
        private RestaurantServiceInterface $restaurantService,
        private RestaurantChainServiceInterface $restaurantChainService,
    ) {}

    public function createRestaurantChain($_, array $args)
    {
        return $this->restaurantChainService->createRestaurantChain(CreateRestaurantChainDTO::from($args['input']));
    }

    public function updateRestaurantChain($_, array $args)
    {
        return $this->restaurantChainService->updateRestaurantChain($args['id'], UpdateRestaurantChainDTO::from($args['input']));
    }

    public function deleteRestaurantChain($_, array $args): bool
    {
        return $this->restaurantChainService->deleteRestaurantChain($args['id']);
    }

    public function createRestaurant($_, array $args)
    {
        return $this->restaurantService->createRestaurant(CreateRestaurantDTO::from($args['input']));
    }

    public function updateRestaurant($_, array $args)
    {
        return $this->restaurantService->updateRestaurant($args['id'], UpdateRestaurantDTO::from($args['input']));
    }

    public function deleteRestaurant($_, array $args): bool
    {
        return $this->restaurantService->deleteRestaurant($args['id']);
    }

    public function assignChainManager($_, array $args)
    {
        return $this->restaurantChainService->assignChainManager($args['user_id'], $args['chain_id']);
    }

    public function assignLocalManager($_, array $args)
    {
        return $this->restaurantService->assignLocalManager($args['user_id'], $args['restaurant_id']);
    }
}
