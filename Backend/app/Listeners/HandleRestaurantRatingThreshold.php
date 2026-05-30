<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\RestaurantRatingThresholdReached;
use App\Services\NotificationService\NotificationServiceInterface;
use App\Services\RestaurantService\RestaurantServiceInterface;

class HandleRestaurantRatingThreshold
{
    public function __construct(
        private RestaurantServiceInterface $restaurantService,
        private NotificationServiceInterface $notificationService,
    ) {}

    public function handle(RestaurantRatingThresholdReached $event): void
    {
        $payload = $event->payload;
        $restaurantId = $payload['restaurantId'];

        $restaurant = $this->restaurantService->getRestaurantById($restaurantId);
        if (! $restaurant) {
            return;
        }

        $restaurant->loadMissing('localManager');
        if (! $restaurant->localManager) {
            return;
        }

        $managerId = $restaurant->localManager->user_id;
        $thresholdType = $payload['thresholdType'];
        $averageRating = $payload['averageRating'];

        if ($thresholdType === 'LOW') {
            $title = 'Aviso: Rating em queda';
            $message = "O restaurante {$restaurant->name} desceu para uma média de {$averageRating}. Considere melhorar o serviço.";
        } else {
            $title = 'Parabéns: Rating excelente!';
            $message = "O restaurante {$restaurant->name} atingiu uma média de {$averageRating}. Continuem o bom trabalho!";
        }

        $this->notificationService->createAndDispatch(
            userId: $managerId,
            type: NotificationType::SYSTEM,
            title: $title,
            message: $message,
            data: [
                'restaurant_id' => $restaurantId,
                'average_rating' => $averageRating,
                'threshold_type' => $thresholdType,
            ]
        );
    }
}
