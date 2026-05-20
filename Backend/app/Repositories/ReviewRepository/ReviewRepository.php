<?php

namespace App\Repositories\ReviewRepository;

use App\DTOs\Review\CreateReviewDTO;
use App\DTOs\Review\UpdateReviewDTO;
use App\Enums\OrderStatus;
use App\Enums\ReviewTargetType;
use App\Models\Order;
use App\Models\Review;

class ReviewRepository implements ReviewRepositoryInterface
{
    public function findById(string $id)
    {
        return Review::find($id);
    }

    public function findByUserId(string $userId, int $pageNumber, int $pageSize)
    {
        return Review::where("user_id", $userId)
            ->orderByDesc('created_at')
            ->paginate($pageSize, ['*'], 'page', $pageNumber);
    }

    public function findByTargetEntity(string $targetEntityId, string $targetEntityType, int $pageNumber, int $pageSize)
    {
        return Review::where('target_id', $targetEntityId)
            ->where('target_type', $targetEntityType)
            ->orderByDesc('created_at')
            ->paginate($pageSize, ['*'], 'page', $pageNumber);
    }

    public function findByUserIdAndId(string $userId, string $reviewId)
    {
        return Review::where('user_id', $userId)->find($reviewId);
    }

    public function userCanReviewTarget(string $userId, string $targetType, string $targetId): bool
    {
        $query = Order::where('user_id', $userId)
            ->where('status', OrderStatus::DELIVERED->value);

        if ($targetType === ReviewTargetType::RESTAURANT->value) {
            $query->where('restaurant_id', $targetId);
        } else {
            $query->whereHas('delivery', fn ($subQuery) => $subQuery->where('courier_id', $targetId));
        }

        return $query->exists();
    }

    public function existsForTarget(string $userId, string $targetType, string $targetId): bool
    {
        return Review::where('user_id', $userId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->exists();
    }

    public function createReview(CreateReviewDTO $data)
    {
        return Review::create($data->toArray());
    }

    public function updateReview(string $id, UpdateReviewDTO $data)
    {
        $review = Review::find($id);
        if ($review) {
            $review->update($data->toArray());
            return $review;
        }
        return null;
    }

    public function deleteReview(string $id)
    {
        $review = Review::find($id);
        if ($review) {
            $review->delete();
            return true;
        }
        return false;
    }

    public function deleteReviewByUserId(string $userId, string $reviewId): bool
    {
        return (bool) Review::where('user_id', $userId)
            ->whereKey($reviewId)
            ->delete();
    }
}
