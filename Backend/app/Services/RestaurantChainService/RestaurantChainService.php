<?php

namespace App\Services\RestaurantChainService;

use App\Aspects\Transactional;
use App\DTOs\RestaurantChain\CreateRestaurantChainDTO;
use App\DTOs\RestaurantChain\SearchRestaurantChainsDTO;
use App\DTOs\RestaurantChain\UpdateRestaurantChainDTO;
use App\Models\ChainManager;
use App\Models\RestaurantChain;
use App\Repositories\ChainManagerRepository\ChainManagerRepositoryInterface;
use App\Repositories\RestaurantChainRepository\RestaurantChainRepositoryInterface;
use App\Repositories\UserRepository\UserRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RestaurantChainService implements RestaurantChainServiceInterface
{
    private RestaurantChainRepositoryInterface $restaurantChainRepository;

    private ChainManagerRepositoryInterface $chainManagerRepository;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        ?RestaurantChainRepositoryInterface $restaurantChainRepository = null,
        ?ChainManagerRepositoryInterface $chainManagerRepository = null,
        ?UserRepositoryInterface $userRepository = null,
    ) {
        $this->restaurantChainRepository = $restaurantChainRepository ?? app(RestaurantChainRepositoryInterface::class);
        $this->chainManagerRepository = $chainManagerRepository ?? app(ChainManagerRepositoryInterface::class);
        $this->userRepository = $userRepository ?? app(UserRepositoryInterface::class);
    }

    public function getRestaurantChainById(string $id): ?RestaurantChain
    {
        return $this->restaurantChainRepository->findById($id);
    }

    public function searchRestaurantChains(SearchRestaurantChainsDTO $filters)
    {
        return $this->restaurantChainRepository->searchRestaurantChains($filters)->items();
    }

    #[Transactional]
    public function createRestaurantChain(CreateRestaurantChainDTO $data): RestaurantChain
    {
        $this->validateInput($data->toArray());

        return $this->restaurantChainRepository->createRestaurantChain($data);
    }

    #[Transactional]
    public function updateRestaurantChain(string $id, UpdateRestaurantChainDTO $data): ?RestaurantChain
    {
        $chain = $this->restaurantChainRepository->findById($id);

        if (! $chain) {
            return null;
        }

        $input = array_filter($data->toArray(), static fn ($value) => $value !== null);
        $this->validateInput([...$chain->toArray(), ...$input]);
        return $this->restaurantChainRepository->updateRestaurantChain($id, $data);
    }

    #[Transactional]
    public function assignChainManager(string $userId, string $chainId): ChainManager
    {
        if (! $this->userRepository->exists($userId)) {
            throw ValidationException::withMessages([
                'user_id' => ['User does not exist.'],
            ]);
        }

        if (! $this->restaurantChainRepository->exists($chainId)) {
            throw ValidationException::withMessages([
                'chain_id' => ['Restaurant chain does not exist.'],
            ]);
        }

        return $this->chainManagerRepository->updateOrCreate($userId, $chainId);
    }

    private function validateInput(array $input): void
    {
        if (empty($input['name'])) {
            throw ValidationException::withMessages(['name' => ['Restaurant chain name is required.']]);
        }
    }
}
