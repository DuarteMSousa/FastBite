<?php

namespace App\Services\ReviewService;

use App\Aspects\Transactional;
use App\DTOs\Review\CreateReviewDTO;
use App\DTOs\Review\UpdateReviewDTO;
use App\Models\Review;
use App\Repositories\ReviewRepository\ReviewRepositoryInterface;
use Illuminate\Validation\ValidationException;

class ReviewService implements ReviewServiceInterface
{
    private ReviewRepositoryInterface $reviews;

    public function __construct(?ReviewRepositoryInterface $reviews = null)
    {
        $this->reviews = $reviews ?? app(ReviewRepositoryInterface::class);
    }

    public function getReviewsByUserId(string $userId, int $page, int $perPage)
    {
        return $this->reviews->findByUserId($userId, $page, $perPage)->items();
    }

    public function getReviewsByTarget(string $targetType, string $targetId, int $page, int $perPage)
    {
        return $this->reviews->findByTargetEntity($targetId, $targetType, $page, $perPage)->items();
    }

    #[Transactional]
    public function updateReview(string $userId, string $reviewId, UpdateReviewDTO $data): ?Review
    {
        $review = $this->reviews->findByUserIdAndId($userId, $reviewId);

        if (! $review) {
            return null;
        }

        $input = array_filter($data->toArray(), static fn ($value) => $value !== null);
        $this->validateInput([...$review->toArray(), ...$input]);
        return $this->reviews->updateReview($reviewId, $data);
    }

    #[Transactional]
    public function deleteReview(string $userId, string $reviewId): bool
    {
        return $this->reviews->deleteReviewByUserId($userId, $reviewId);
    }

    #[Transactional]
    public function createReview(CreateReviewDTO $data): Review
    {
        $this->validateInput($data->toArray());
        $this->assertUserCanReviewTarget($data);
        $this->assertNotDuplicate($data);

        return $this->reviews->createReview($data);
    }

    private function assertUserCanReviewTarget(CreateReviewDTO $data): void
    {
        if (! $this->reviews->userCanReviewTarget($data->user_id, $data->target_type->value, $data->target_id)) {
            throw ValidationException::withMessages([
                'target_id' => 'You can only review after a delivered order with this target.',
            ]);
        }
    }

    private function assertNotDuplicate(CreateReviewDTO $data): void
    {
        if ($this->reviews->existsForTarget($data->user_id, $data->target_type->value, $data->target_id)) {
            throw ValidationException::withMessages([
                'target_id' => 'You have already reviewed this target.',
            ]);
        }
    }

    private function validateInput(array $input): void
    {
        $errors = [];
        $rating = (int) ($input['rating'] ?? 0);

        if ($rating < 1 || $rating > 5) {
            $errors['rating'][] = 'Rating must be between 1 and 5.';
        }

        if (empty($input['target_type']) || ! in_array($input['target_type'], ['RESTAURANT', 'COURIER'], true)) {
            $errors['target_type'][] = 'Invalid review target type.';
        }

        if (empty($input['target_id'])) {
            $errors['target_id'][] = 'Review target id is required.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
