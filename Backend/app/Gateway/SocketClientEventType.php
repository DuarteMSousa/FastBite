<?php

namespace App\Gateway;

enum SocketClientEventType: string
{
    case HELLO = 'hello';
    case SUBSCRIBE = 'subscribe';
    case UNSUBSCRIBE = 'unsubscribe';
    case CHAT_MESSAGE_SEND = 'chat.message.send';
    case COURIER_STATUS_SET = 'courier.status.set';
    case COURIER_POSITION_SET = 'courier.position.set';
}
