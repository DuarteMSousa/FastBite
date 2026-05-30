<?php

namespace App\Services\UserAddressService;

use App\Aspects\Transactional;
use App\DTOs\UserAddress\CreateUserAddressDTO;
use App\DTOs\UserAddress\UpdateUserAddressDTO;
use App\Models\UserAddress;
use App\Repositories\UserAddressRepository\UserAddressRepositoryInterface;
use Illuminate\Validation\ValidationException;

class UserAddressService implements UserAddressServiceInterface
{
    private UserAddressRepositoryInterface $userAddressRepository;

    public function __construct(?UserAddressRepositoryInterface $userAddressRepository = null)
    {
        $this->userAddressRepository = $userAddressRepository ?? app(UserAddressRepositoryInterface::class);
    }

    public function getUserAddressesByUserId(string $userId)
    {
        return $this->userAddressRepository->findByUserId($userId);
    }

    #[Transactional]
    public function createUserAddress(string $userId, CreateUserAddressDTO $data): UserAddress
    {
        if ($data->is_default === true) {
            $this->clearDefault($userId);
        }

        $this->createPayload($data);

        return $this->userAddressRepository->createForUser($userId, $data);
    }

    #[Transactional]
    public function updateUserAddress(string $userId, string $addressId, UpdateUserAddressDTO $data): ?UserAddress
    {
        $address = $this->userAddressRepository->findByUserIdAndId($userId, $addressId);

        if (! $address) {
            return null;
        }

        if ($data->is_default === true) {
            $this->clearDefault($userId);
        }

        $this->validateInput([...$address->toArray(), ...array_filter($data->toArray(), static fn ($value) => $value !== null)]);
        return $this->userAddressRepository->updateForUser($userId, $addressId, $data);
    }

    #[Transactional]
    public function deleteUserAddress(string $userId, string $addressId): bool
    {
        return $this->userAddressRepository->deleteForUser($userId, $addressId);
    }

    #[Transactional]
    public function setDefaultUserAddress(string $userId, string $addressId): ?UserAddress
    {
        $address = $this->userAddressRepository->findByUserIdAndId($userId, $addressId);

        if (! $address) {
            return null;
        }

        $this->clearDefault($userId);
        return $this->userAddressRepository->setDefault($userId, $addressId);
    }

    private function clearDefault(string $userId): void
    {
        $this->userAddressRepository->clearDefault($userId);
    }

    private function createPayload(CreateUserAddressDTO $data): array
    {
        $payload = $data->toArray();
        $this->validateInput($payload);

        return $payload;
    }

    private function updatePayload(UpdateUserAddressDTO $data): array
    {
        return array_filter($data->toArray(), static fn ($value) => $value !== null);
    }

    private function validateInput(array $input): void
    {
        $errors = [];

        foreach (['street', 'city', 'postal_code', 'country'] as $field) {
            if (empty($input[$field])) {
                $errors[$field][] = "{$field} is required.";
            }
        }

        foreach (['latitude', 'longitude'] as $field) {
            if (! isset($input[$field]) || ! is_numeric($input[$field])) {
                $errors[$field][] = "{$field} must be numeric.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
