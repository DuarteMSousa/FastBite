<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'PENDING';
    case COURIER_ASSIGNED = 'COURIER_ASSIGNED';
    case CONFIRMED = 'CONFIRMED';
    case PREPARING = 'PREPARING';
    case READY = 'READY';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';

    public function toEventType(): OrderEventType
    {
        return match ($this) {
            self::PENDING => OrderEventType::ORDER_CREATED,
            self::COURIER_ASSIGNED => OrderEventType::ORDER_COURIER_ASSIGNED,
            self::CONFIRMED => OrderEventType::ORDER_CONFIRMED,
            self::PREPARING => OrderEventType::ORDER_PREPARING,
            self::READY => OrderEventType::ORDER_READY,
            self::OUT_FOR_DELIVERY => OrderEventType::ORDER_OUT_FOR_DELIVERY,
            self::DELIVERED => OrderEventType::ORDER_DELIVERED,
            self::CANCELLED => OrderEventType::ORDER_CANCELLED,
        };
    }
}
