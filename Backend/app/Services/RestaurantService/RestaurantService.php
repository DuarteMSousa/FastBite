<?php

namespace App\Services\RestaurantService;

use App\Aspects\Transactional;
use App\DTOs\Restaurant\CreateRestaurantDTO;
use App\DTOs\Restaurant\SearchRestaurantsDTO;
use App\DTOs\Restaurant\UpdateRestaurantDTO;
use App\Models\ChainManager;
use App\Models\LocalManager;
use App\Models\Restaurant;
use App\Models\RestaurantAddress;
use App\Models\RestaurantChain;
use App\Models\User;
use App\Repositories\RestaurantRepository\RestaurantRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RestaurantService implements RestaurantServiceInterface
{
    private const ADDRESS_FIELDS = ['street', 'city', 'postal_code', 'country', 'latitude', 'longitude'];

    private array $with = ['chain', 'address'];

    public function __construct(private RestaurantRepositoryInterface $restaurantRepository) {}

    public function searchRestaurants(SearchRestaurantsDTO $filters)
    {
        return $this->restaurantRepository->searchRestaurants($filters)->items();
    }

    public function getRestaurantById(string $id): ?Restaurant
    {
        return Restaurant::query()->with($this->with)->find($id);
    }

    #[Transactional]
    public function createRestaurant(string $actorUserId, CreateRestaurantDTO $data): Restaurant
    {
        $this->validateInput($data->toArray());
        $this->validateAddressInput($data);

        $restaurant = Restaurant::query()->create([
            'chain_id' => $data->chain_id,
            'name' => $data->name,
            'opening_hours' => $data->opening_hours,
            'closing_hours' => $data->closing_hours,
            'delivery_radius' => $data->delivery_radius,
        ]);

        if ($this->hasAddressInput($data)) {
            RestaurantAddress::query()->create([
                'restaurant_id' => $restaurant->id,
                ...$this->addressPayload($data),
            ]);
        }

        return $restaurant->load($this->with);
    }

    #[Transactional]
    public function updateRestaurant(string $actorUserId, string $id, UpdateRestaurantDTO $data): ?Restaurant
    {
        $restaurant = Restaurant::query()->find($id);

        if (! $restaurant) {
            return null;
        }

        $input = array_filter($data->toArray(), static fn ($value) => $value !== null);
        $this->validateInput([...$restaurant->toArray(), ...$input], true);
        $this->validateAddressUpdateInput($restaurant, $data);
        $restaurant->update(array_filter([
            'chain_id' => $data->chain_id,
            'name' => $data->name,
            'opening_hours' => $data->opening_hours,
            'closing_hours' => $data->closing_hours,
            'delivery_radius' => $data->delivery_radius,
        ], static fn ($value) => $value !== null));

        $this->updateAddress($restaurant, $data);

        return $restaurant->load($this->with);
    }

    #[Transactional]
    public function deleteRestaurant(string $actorUserId, string $id): bool
    {
        return (bool) Restaurant::query()->whereKey($id)->delete();
    }

    public function getRestaurantsByChainId(string $chainId)
    {
        return Restaurant::query()
            ->with($this->with)
            ->where('chain_id', $chainId)
            ->orderBy('name')
            ->get();
    }

    public function getRestaurantByLocalManagerUserId(string $userId): ?Restaurant
    {
        $manager = LocalManager::query()->where('user_id', $userId)->first();

        return $manager?->restaurant()->with($this->with)->first();
    }

    public function getRestaurantByManagerUserId(string $userId): ?Restaurant
    {
        return $this->getRestaurantsByManagerUserId($userId)->first();
    }

    public function getRestaurantsByManagerUserId(string $userId)
    {
        $localManager = LocalManager::query()
            ->with(['restaurant.chain', 'restaurant.address'])
            ->where('user_id', $userId)
            ->whereNotNull('restaurant_id')
            ->first();

        if ($localManager?->restaurant) {
            return collect([$localManager->restaurant]);
        }

        $chainManager = ChainManager::query()
            ->where('user_id', $userId)
            ->whereNotNull('chain_id')
            ->first();

        if (! $chainManager) {
            return collect();
        }

        return Restaurant::query()
            ->with($this->with)
            ->where('chain_id', $chainManager->chain_id)
            ->orderBy('name')
            ->get();
    }

    public function getRestaurantChainByManagerUserId(string $userId): ?RestaurantChain
    {
        return ChainManager::query()
            ->where('user_id', $userId)
            ->whereNotNull('chain_id')
            ->first()
            ?->chain;
    }

    #[Transactional]
    public function assignChainManager(string $userId, string $chainId): ChainManager
    {
        if (! User::query()->whereKey($userId)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => ['User does not exist.'],
            ]);
        }

        if (! RestaurantChain::query()->whereKey($chainId)->exists()) {
            throw ValidationException::withMessages([
                'chain_id' => ['Restaurant chain does not exist.'],
            ]);
        }

        return ChainManager::query()->updateOrCreate(
            ['user_id' => $userId],
            ['chain_id' => $chainId],
        )->load(['user', 'chain']);
    }

    #[Transactional]
    public function assignLocalManager(string $userId, string $restaurantId): LocalManager
    {
        if (! User::query()->whereKey($userId)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => ['User does not exist.'],
            ]);
        }

        if (! Restaurant::query()->whereKey($restaurantId)->exists()) {
            throw ValidationException::withMessages([
                'restaurant_id' => ['Restaurant does not exist.'],
            ]);
        }

        return LocalManager::query()->updateOrCreate(
            ['user_id' => $userId],
            ['restaurant_id' => $restaurantId],
        )->load(['user', 'restaurant']);
    }

    private function validateInput(array $input, bool $isUpdate = false): void
    {
        $errors = [];

        foreach (['name', 'opening_hours', 'closing_hours'] as $field) {
            if (empty($input[$field])) {
                $errors[$field][] = "{$field} is required.";
            }
        }

        if (empty($input['chain_id']) || ! RestaurantChain::query()->whereKey($input['chain_id'])->exists()) {
            $errors['chain_id'][] = 'Restaurant chain does not exist.';
        }

        if (! isset($input['delivery_radius']) || (float) $input['delivery_radius'] < 0) {
            $errors['delivery_radius'][] = 'Delivery radius must be greater than or equal to zero.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function hasAddressInput(object $data): bool
    {
        foreach (self::ADDRESS_FIELDS as $field) {
            if ($data->{$field} !== null) {
                return true;
            }
        }

        return false;
    }

    private function validateAddressInput(CreateRestaurantDTO $data): void
    {
        if (! $this->hasAddressInput($data)) {
            return;
        }

        $this->validateRequiredAddressFields($this->addressPayload($data), 'creating');
    }

    private function validateAddressUpdateInput(Restaurant $restaurant, UpdateRestaurantDTO $data): void
    {
        if (! $this->hasAddressInput($data)) {
            return;
        }

        $this->validateRequiredAddressFields($this->addressPayload($data, $restaurant->address), 'updating');
    }

    private function validateRequiredAddressFields(array $payload, string $action): void
    {
        $errors = [];
        foreach ($payload as $field => $value) {
            if ($value === null || $value === '') {
                $errors[$field][] = "{$field} is required when {$action} a restaurant address.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function updateAddress(Restaurant $restaurant, UpdateRestaurantDTO $data): void
    {
        if (! $this->hasAddressInput($data)) {
            return;
        }

        RestaurantAddress::query()->updateOrCreate(
            ['restaurant_id' => $restaurant->id],
            $this->addressPayload($data, $restaurant->address),
        );
    }

    private function addressPayload(object $data, ?RestaurantAddress $fallbackAddress = null): array
    {
        $payload = [];

        foreach (self::ADDRESS_FIELDS as $field) {
            $payload[$field] = $data->{$field} ?? $fallbackAddress?->{$field};
        }

        return $payload;
    }
}
