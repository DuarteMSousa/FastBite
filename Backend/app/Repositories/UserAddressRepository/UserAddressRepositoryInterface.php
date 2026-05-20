<?php

namespace App\Repositories\UserAddressRepository;

use App\DTOs\UserAddress\CreateUserAddressDTO;
use App\DTOs\UserAddress\UpdateUserAddressDTO;

interface UserAddressRepositoryInterface
{
    public function findByUserId(string $userId);

    public function findByUserIdAndId(string $userId, string $addressId);

    public function findByUserIdAndIdOrFail(string $userId, string $addressId);

    public function createForUser(string $userId, CreateUserAddressDTO $data);

    public function updateForUser(string $userId, string $addressId, UpdateUserAddressDTO $data);

    public function deleteForUser(string $userId, string $addressId): bool;

    public function clearDefault(string $userId): void;

    public function setDefault(string $userId, string $addressId);
}
