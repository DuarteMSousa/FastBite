<?php

namespace App\Enums;

enum OutboxAggregateType: string
{
    case ORDER = 'order';
    case PAYMENT = 'payment';
    case DELIVERY = 'delivery';
    case DELIVERY_OFFER = 'delivery_offer';
    case CHAT = 'chat';
    case NOTIFICATION = 'notification';
    case RESTAURANT = 'restaurant';
    case USER = 'user';
}
