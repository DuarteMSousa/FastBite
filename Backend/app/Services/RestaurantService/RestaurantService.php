<?php

namespace App\Services\RestaurantService;

use App\Aspects\Transactional;
use App\DTOs\Restaurant\CreateRestaurantDTO;
use App\DTOs\Restaurant\SearchRestaurantsDTO;
use App\DTOs\Restaurant\UpdateRestaurantDTO;
use App\Models\LocalManager;
use App\Models\Restaurant;
use App\Models\RestaurantAddress;
use App\Models\RestaurantChain;
use App\Repositories\ChainManagerRepository\ChainManagerRepositoryInterface;
use App\Repositories\LocalManagerRepository\LocalManagerRepositoryInterface;
use App\Repositories\RestaurantRepository\RestaurantRepositoryInterface;
use App\Repositories\RestaurantChainRepository\RestaurantChainRepositoryInterface;
use App\Repositories\UserRepository\UserRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RestaurantService implements RestaurantServiceInterface
{
    private const ADDRESS_FIELDS = ['street', 'city', 'postal_code', 'country', 'latitude', 'longitude'];

    private array $with = ['chain', 'address'];

    private RestaurantRepositoryInterface $restaurantRepository;

    private RestaurantChainRepositoryInterface $restaurantChainRepository;

    private ChainManagerRepositoryInterface $chainManagerRepository;

    private LocalManagerRepositoryInterface $localManagerRepository;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        ?RestaurantRepositoryInterface $restaurantRepository = null,
        ?RestaurantChainRepositoryInterface $restaurantChainRepository = null,
        ?ChainManagerRepositoryInterface $chainManagerRepository = null,
        ?LocalManagerRepositoryInterface $localManagerRepository = null,
        ?UserRepositoryInterface $userRepository = null,
    ) {
        $this->restaurantRepository = $restaurantRepository ?? app(RestaurantRepositoryInterface::class);
        $this->restaurantChainRepository = $restaurantChainRepository ?? app(RestaurantChainRepositoryInterface::class);
        $this->chainManagerRepository = $chainManagerRepository ?? app(ChainManagerRepositoryInterface::class);
        $this->localManagerRepository = $localManagerRepository ?? app(LocalManagerRepositoryInterface::class);
        $this->userRepository = $userRepository ?? app(UserRepositoryInterface::class);
    }

    public function searchRestaurants(SearchRestaurantsDTO $filters)
    {
        return $this->restaurantRepository->searchRestaurants($filters)->items();
    }

    public function getRestaurantById(string $id): ?Restaurant
    {
        return $this->restaurantRepository->findById($id);
    }

    #[Transactional]
    public function createRestaurant(CreateRestaurantDTO $data): Restaurant
    {
        $this->validateInput($data->toArray());
        $this->validateAddressInput($data);

        $restaurant = $this->restaurantRepository->createRestaurant($data);

        if ($this->hasAddressInput($data)) {
            $this->restaurantRepository->upsertAddress($restaurant->id, $this->addressPayload($data));
        }

        return $restaurant->load($this->with);
    }

    #[Transactional]
    public function updateRestaurant(string $id, UpdateRestaurantDTO $data): ?Restaurant
    {
        $restaurant = $this->restaurantRepository->findById($id);

        if (! $restaurant) {
            return null;
        }

        $input = array_filter($data->toArray(), static fn ($value) => $value !== null);
        $this->validateInput([...$restaurant->toArray(), ...$input], true);
        $this->validateAddressUpdateInput($restaurant, $data);
        $restaurant = $this->restaurantRepository->updateRestaurant($id, $data);

        $this->updateAddress($restaurant, $data);

        return $restaurant->load($this->with);
    }

    #[Transactional]
    public function deleteRestaurant(string $id): bool
    {
        return $this->restaurantRepository->deleteRestaurant($id);
    }

    public function getRestaurantsByChainId(string $chainId)
    {
        return $this->restaurantRepository->findByChainId($chainId);
    }

    public function getRestaurantByLocalManagerUserId(string $userId): ?Restaurant
    {
        $manager = $this->localManagerRepository->findByUserId($userId);

        return $manager?->restaurant;
    }

    public function getRestaurantByManagerUserId(string $userId): ?Restaurant
    {
        return $this->getRestaurantsByManagerUserId($userId)->first();
    }

    public function getRestaurantsByManagerUserId(string $userId)
    {
        $localManager = $this->localManagerRepository->findByUserId($userId);

        if ($localManager?->restaurant) {
            return collect([$localManager->restaurant]);
        }

        $chainManager = $this->chainManagerRepository->findByUserId($userId);

        if (! $chainManager) {
            return collect();
        }

        return $this->restaurantRepository->findByChainId($chainManager->chain_id);
    }

    public function getRestaurantChainByManagerUserId(string $userId): ?RestaurantChain
    {
        return $this->chainManagerRepository->findByUserId($userId)?->chain;
    }

    #[Transactional]
    public function assignLocalManager(string $userId, string $restaurantId): LocalManager
    {
        if (! $this->userRepository->exists($userId)) {
            throw ValidationException::withMessages([
                'user_id' => ['User does not exist.'],
            ]);
        }

        if (! $this->restaurantRepository->exists($restaurantId)) {
            throw ValidationException::withMessages([
                'restaurant_id' => ['Restaurant does not exist.'],
            ]);
        }

        return $this->localManagerRepository->updateOrCreate($userId, $restaurantId);
    }

    private function validateInput(array $input, bool $isUpdate = false): void
    {
        $errors = [];

        foreach (['name', 'opening_hours', 'closing_hours'] as $field) {
            if (empty($input[$field])) {
                $errors[$field][] = "{$field} is required.";
            }
        }

        if (empty($input['chain_id']) || ! $this->restaurantChainRepository->exists($input['chain_id'])) {
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

        $this->restaurantRepository->upsertAddress($restaurant->id, $this->addressPayload($data, $restaurant->address));
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
