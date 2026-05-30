<?php

namespace App\Services\ReviewService;

use App\Aspects\Transactional;
use App\DTOs\Review\CreateReviewDTO;
use App\DTOs\Review\UpdateReviewDTO;
use App\Enums\OutboxAggregateType;
use App\Enums\OutboxEventType;
use App\Enums\ReviewTargetType;
use App\Models\Review;
use App\Repositories\RestaurantRepository\RestaurantRepositoryInterface;
use App\Repositories\ReviewRepository\ReviewRepositoryInterface;
use App\Services\OutboxService;
use BackedEnum;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReviewService implements ReviewServiceInterface
{
    private const RATING_LOW_THRESHOLD = 3.0;
    private const RATING_HIGH_THRESHOLD = 4.5;

    private ReviewRepositoryInterface $reviewRepository;
    private RestaurantRepositoryInterface $restaurantRepository;

    public function __construct(
        ?ReviewRepositoryInterface $reviewRepository = null,
        ?RestaurantRepositoryInterface $restaurantRepository = null,
    ) {
        $this->reviewRepository = $reviewRepository ?? app(ReviewRepositoryInterface::class);
        $this->restaurantRepository = $restaurantRepository ?? app(RestaurantRepositoryInterface::class);
    }

    public function getReviewsByUserId(string $userId, int $page, int $perPage)
    {
        return $this->reviewRepository->findByUserId($userId, $page, $perPage)->items();
    }

    public function getReviewsByTarget(string $targetType, string $targetId, int $page, int $perPage)
    {
        return $this->reviewRepository->findByTargetEntity($targetId, $targetType, $page, $perPage)->items();
    }

    #[Transactional]
    public function updateReview(string $userId, string $reviewId, UpdateReviewDTO $data): ?Review
    {
        $review = $this->reviewRepository->findByUserIdAndId($userId, $reviewId);

        if (! $review) {
            return null;
        }

        $input = array_filter($data->toArray(), static fn ($value) => $value !== null);
        $this->validateInput([...$review->toArray(), ...$input]);
        return $this->reviewRepository->updateReview($reviewId, $data);
    }

    #[Transactional]
    public function deleteReview(string $userId, string $reviewId): bool
    {
        return $this->reviewRepository->deleteReviewByUserId($userId, $reviewId);
    }

    #[Transactional]
    public function createReview(CreateReviewDTO $data): Review
    {
        $this->validateInput($data->toArray());
        $this->assertUserCanReviewTarget($data);
        $this->assertNotDuplicate($data);

        $review = $this->reviewRepository->createReview($data);

        if ($data->target_type === ReviewTargetType::RESTAURANT) {
            $this->updateRestaurantRatingAndCheckThreshold($data->target_id, $data->rating);
        }

        return $review;
    }

    private function assertUserCanReviewTarget(CreateReviewDTO $data): void
    {
        if (! $this->reviewRepository->userCanReviewTarget($data->user_id, $data->target_type->value, $data->target_id)) {
            throw ValidationException::withMessages([
                'target_id' => 'You can only review after a delivered order with this target.',
            ]);
        }
    }

    private function assertNotDuplicate(CreateReviewDTO $data): void
    {
        if ($this->reviewRepository->existsForTarget($data->user_id, $data->target_type->value, $data->target_id)) {
            throw ValidationException::withMessages([
                'target_id' => 'You have already reviewed this target.',
            ]);
        }
    }

    private function validateInput(array $input): void
    {
        $errors = [];
        $rating = (int) ($input['rating'] ?? 0);
        $targetType = $this->enumValue($input['target_type'] ?? null);

        if ($rating < 1 || $rating > 5) {
            $errors['rating'][] = 'Rating must be between 1 and 5.';
        }

        if (empty($targetType) || ! in_array($targetType, ['RESTAURANT', 'COURIER'], true)) {
            $errors['target_type'][] = 'Invalid review target type.';
        }

        if (empty($input['target_id'])) {
            $errors['target_id'][] = 'Review target id is required.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_string($value) ? $value : null;
    }

    private function updateRestaurantRatingAndCheckThreshold(string $restaurantId, int $newRating): void
    {
        $restaurant = $this->restaurantRepository->findByIdOrFail($restaurantId);

        $previousRatingCount = (int) $restaurant->rating_count;
        $previousRatingSum = (float) $restaurant->rating_sum;
        $previousAverage = $previousRatingCount > 0
            ? $previousRatingSum / $previousRatingCount
            : 0.0;

        $newRatingSum = $previousRatingSum + $newRating;
        $newRatingCount = $previousRatingCount + 1;

        $this->restaurantRepository->updateRating($restaurantId, $newRatingSum, $newRatingCount);

        $newAverage = $newRatingSum / $newRatingCount;

        $thresholdType = null;

        if ($newAverage <= self::RATING_LOW_THRESHOLD && $previousAverage > self::RATING_LOW_THRESHOLD) {
            $thresholdType = 'LOW';
        } elseif ($newAverage >= self::RATING_HIGH_THRESHOLD && $previousAverage < self::RATING_HIGH_THRESHOLD) {
            $thresholdType = 'HIGH';
        }

        if ($thresholdType !== null) {
            app(OutboxService::class)->enqueue(
                OutboxAggregateType::RESTAURANT,
                $restaurantId,
                OutboxEventType::RESTAURANT_RATING_THRESHOLD_REACHED,
                [
                    'eventId' => (string) Str::uuid(),
                    'eventName' => OutboxEventType::RESTAURANT_RATING_THRESHOLD_REACHED->value,
                    'aggregateType' => 'restaurant',
                    'aggregateId' => $restaurantId,
                    'restaurantId' => $restaurantId,
                    'restaurantName' => $restaurant->name,
                    'averageRating' => round($newAverage, 2),
                    'previousAverageRating' => round($previousAverage, 2),
                    'ratingCount' => $newRatingCount,
                    'thresholdType' => $thresholdType,
                    'occurredAt' => now()->toIso8601String(),
                ]
            );
        }
    }
}
