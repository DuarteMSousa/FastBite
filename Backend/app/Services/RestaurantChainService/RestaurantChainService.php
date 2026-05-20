<?php

namespace App\Services\RestaurantChainService;

use App\Aspects\Transactional;
use App\DTOs\RestaurantChain\CreateRestaurantChainDTO;
use App\DTOs\RestaurantChain\UpdateRestaurantChainDTO;
use App\Models\RestaurantChain;
use App\Repositories\RestaurantChainRepository\RestaurantChainRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RestaurantChainService implements RestaurantChainServiceInterface
{
    private RestaurantChainRepositoryInterface $chains;

    public function __construct(?RestaurantChainRepositoryInterface $chains = null)
    {
        $this->chains = $chains ?? app(RestaurantChainRepositoryInterface::class);
    }

    public function getRestaurantChainById(string $id): ?RestaurantChain
    {
        return $this->chains->findById($id);
    }

    public function getAllRestaurantChains(int $limit = 100)
    {
        return $this->chains->findAll($limit);
    }

    #[Transactional]
    public function createRestaurantChain(CreateRestaurantChainDTO $data): RestaurantChain
    {
        $this->validateInput($data->toArray());

        return $this->chains->createRestaurantChain($data);
    }

    #[Transactional]
    public function updateRestaurantChain(string $id, UpdateRestaurantChainDTO $data): ?RestaurantChain
    {
        $chain = $this->chains->findById($id);

        if (! $chain) {
            return null;
        }

        $input = array_filter($data->toArray(), static fn ($value) => $value !== null);
        $this->validateInput([...$chain->toArray(), ...$input]);
        return $this->chains->updateRestaurantChain($id, $data);
    }

    #[Transactional]
    public function deleteRestaurantChain(string $id): bool
    {
        return $this->chains->deleteRestaurantChain($id);
    }

    private function validateInput(array $input): void
    {
        if (empty($input['name'])) {
            throw ValidationException::withMessages(['name' => ['Restaurant chain name is required.']]);
        }
    }
}
