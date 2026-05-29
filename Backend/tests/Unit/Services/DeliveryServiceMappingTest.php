<?php

namespace Tests\Unit\Services;

use App\Enums\DeliveryEventType;
use App\Enums\DeliveryStatus;
use App\Services\DeliveryService\DeliveryService;
use ReflectionClass;
use Tests\TestCase;

class DeliveryServiceMappingTest extends TestCase
{
    public function test_maps_delivery_status_to_event_type(): void
    {
        $service = app(DeliveryService::class);

        $this->assertSame(
            DeliveryEventType::DELIVERY_PICKED_UP,
            $this->invoke($service, 'eventTypeForStatus', DeliveryStatus::PICKED_UP)
        );
        $this->assertSame(
            DeliveryEventType::DELIVERY_IN_TRANSIT,
            $this->invoke($service, 'eventTypeForStatus', DeliveryStatus::IN_TRANSIT)
        );
        $this->assertSame(
            DeliveryEventType::DELIVERY_DELIVERED,
            $this->invoke($service, 'eventTypeForStatus', DeliveryStatus::DELIVERED)
        );
        $this->assertSame(
            DeliveryEventType::DELIVERY_FAILED,
            $this->invoke($service, 'eventTypeForStatus', DeliveryStatus::FAILED)
        );
    }

    public function test_scores_courier_candidates_with_distance_predicate(): void
    {
        $service = app(DeliveryService::class);
        $restaurantAddress = (object) [
            'latitude' => 41.1496,
            'longitude' => -8.6109,
        ];
        $scoreCourier = $this->invoke($service, 'scoreCourierCandidate', $restaurantAddress, 1.0);

        $nearCourier = (object) [
            'latitude' => 41.1496,
            'longitude' => -8.6109,
        ];
        $farCourier = (object) [
            'latitude' => 40.0,
            'longitude' => -8.0,
        ];
        $unknownLocationCourier = (object) [
            'latitude' => null,
            'longitude' => null,
        ];

        $this->assertSame($nearCourier, $scoreCourier($nearCourier)['courier']);
        $this->assertSame(0.0, $scoreCourier($nearCourier)['distance']);
        $this->assertNull($scoreCourier($farCourier));
        $this->assertNull($scoreCourier($unknownLocationCourier)['distance']);
    }

    private function invoke(object $target, string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionClass($target);
        $reflectedMethod = $reflection->getMethod($method);

        return $reflectedMethod->invoke($target, ...$args);
    }
}
