<?php

namespace App\Gateway;

enum SocketServerEventType: string
{
    case GATEWAY_READY = 'gateway.ready';
    case GATEWAY_ERROR = 'gateway.error';
    case GATEWAY_HEARTBEAT = 'gateway.heartbeat';
    case HELLO_ACK = 'hello.ack';
    case SUBSCRIBE_ACK = 'subscribe.ack';
    case UNSUBSCRIBE_ACK = 'unsubscribe.ack';
    case CHAT_MESSAGE_SEND_ACK = 'chat.message.send.ack';
    case COURIER_STATUS_ACK = 'courier.status.ack';
    case COURIER_POSITION_ACK = 'courier.position.ack';
}
