<?php

namespace App\Repositories\ChainManagerRepository;

use App\DTOs\ChainManager\CreateChainManagerDTO;
use App\Models\ChainManager;

class ChainManagerRepository implements ChainManagerRepositoryInterface
{
    public function findByUserId(string $userId)
    {
        return ChainManager::with('chain')->where('user_id', $userId)->first();
    }

    public function findByChainId(string $chainId)
    {
        return ChainManager::where('chain_id', $chainId)->get();
    }

    public function createChainManager(CreateChainManagerDTO $data)
    {
        return ChainManager::create($data->toArray());
    }

    public function updateOrCreate(string $userId, string $chainId)
    {
        return ChainManager::updateOrCreate(
            ['user_id' => $userId],
            ['chain_id' => $chainId],
        )->load(['user', 'chain']);
    }

    public function deleteChainManager(string $userId)
    {
        $chainManager = ChainManager::where('user_id', $userId)->first();

        if (!$chainManager) {
            return false;
        }

        $chainManager->delete();

        return true;
    }
}
