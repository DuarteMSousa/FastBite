<?php

namespace App\Repositories\UserAddressRepository;

use App\DTOs\UserAddress\CreateUserAddressDTO;
use App\DTOs\UserAddress\UpdateUserAddressDTO;
use App\Models\UserAddress;

class UserAddressRepository implements UserAddressRepositoryInterface
{
    public function findByUserId(string $userId)
    {
        return UserAddress::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();
    }

    public function findByUserIdAndId(string $userId, string $addressId)
    {
        return UserAddress::where('user_id', $userId)->find($addressId);
    }

    public function findByUserIdAndIdOrFail(string $userId, string $addressId)
    {
        return UserAddress::where('user_id', $userId)->findOrFail($addressId);
    }

    public function createForUser(string $userId, CreateUserAddressDTO $data)
    {
        return UserAddress::create([
            'user_id' => $userId,
            ...$data->toArray(),
        ]);
    }

    public function updateForUser(string $userId, string $addressId, UpdateUserAddressDTO $data)
    {
        $address = $this->findByUserIdAndId($userId, $addressId);

        if (! $address) {
            return null;
        }

        $address->update(array_filter($data->toArray(), static fn ($value) => $value !== null));

        return $address;
    }

    public function deleteForUser(string $userId, string $addressId): bool
    {
        return (bool) UserAddress::where('user_id', $userId)
            ->whereKey($addressId)
            ->delete();
    }

    public function clearDefault(string $userId): void
    {
        UserAddress::where('user_id', $userId)->update(['is_default' => false]);
    }

    public function setDefault(string $userId, string $addressId)
    {
        $address = $this->findByUserIdAndId($userId, $addressId);

        if (! $address) {
            return null;
        }

        $address->update(['is_default' => true]);

        return $address;
    }
}
