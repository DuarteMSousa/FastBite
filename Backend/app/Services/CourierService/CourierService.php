<?php

namespace App\Services\CourierService;

use App\Aspects\Transactional;
use App\DTOs\Courier\CreateCourierDTO;
use App\DTOs\Courier\UpdateCourierDTO;
use App\Enums\CourierStatus;
use App\Models\Courier;
use App\Repositories\CourierRepository\CourierRepositoryInterface;
use App\Repositories\UserRepository\UserRepositoryInterface;
use App\Services\DeliveryService\DeliveryServiceInterface;
use Illuminate\Validation\ValidationException;

class CourierService implements CourierServiceInterface
{
    private CourierRepositoryInterface $courierRepository;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        ?CourierRepositoryInterface $courierRepository = null,
        ?UserRepositoryInterface $userRepository = null,
    ) {
        $this->courierRepository = $courierRepository ?? app(CourierRepositoryInterface::class);
        $this->userRepository = $userRepository ?? app(UserRepositoryInterface::class);
    }

    public function getCourierByUserId(string $userId): ?Courier
    {
        return $this->courierRepository->getByUserIdWithUser($userId);
    }

    #[Transactional]
    public function ensureCourierProfile(string $userId): Courier
    {
        if (! $this->userRepository->exists($userId)) {
            throw ValidationException::withMessages([
                'user_id' => ['User does not exist.'],
            ]);
        }

        $courier = $this->courierRepository->findByUserId($userId)
            ?? $this->courierRepository->createCourier(new CreateCourierDTO(user_id: $userId));

        return $courier->load('user');
    }

    #[Transactional]
    public function updateCourierStatus(string $userId, string $status): Courier
    {
        $courier = $this->courierRepository->getByUserIdOrFail($userId);

        if (! CourierStatus::tryFrom($status)) {
            throw ValidationException::withMessages([
                'status' => 'Unknown courier status.',
            ]);
        }

        if ($status === CourierStatus::OFFLINE->value && $this->courierHasActiveDelivery($userId)) {
            throw ValidationException::withMessages([
                'status' => 'Courier has an active delivery and cannot go offline.',
            ]);
        }

        $this->courierRepository->updateCourier($userId, new UpdateCourierDTO(status: $status));

        if ($status === CourierStatus::AVAILABLE->value) {
            app(DeliveryServiceInterface::class)->dispatchPendingCourierAssignments();
        }

        return $courier->refresh()->load('user');
    }

    private function courierHasActiveDelivery(string $courierId): bool
    {
        return $this->courierRepository->hasActiveDelivery($courierId);
    }

    #[Transactional]
    public function updateCourierLocation(string $courierId, float $latitude, float $longitude): Courier
    {
        $courier = $this->courierRepository->updateCourier($courierId, new UpdateCourierDTO(
            latitude: $latitude,
            longitude: $longitude,
            lastLocationUpdate: now(),
        ));

        return $courier->refresh()->load('user');
    }
}
