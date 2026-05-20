<?php

return [
    'enabled' => env('GATEWAY_WORKER_ENABLED', true),

    'websocket_host' => env('GATEWAY_WORKER_WEBSOCKET_HOST', '0.0.0.0'),
    'websocket_port' => env('GATEWAY_WORKER_WEBSOCKET_PORT', 8090),

    'register_host' => env('GATEWAY_WORKER_REGISTER_HOST', '127.0.0.1'),
    'register_port' => env('GATEWAY_WORKER_REGISTER_PORT', 1236),

    'lan_ip' => env('GATEWAY_WORKER_LAN_IP', '127.0.0.1'),
    'start_port' => env('GATEWAY_WORKER_START_PORT', 2300),

    'gateway_processes' => env('GATEWAY_WORKER_GATEWAY_PROCESSES', 2),
    'business_worker_processes' => env('GATEWAY_WORKER_BUSINESS_WORKER_PROCESSES', 4),

    'heartbeat_interval' => env('GATEWAY_WORKER_HEARTBEAT_INTERVAL', 30),
    'heartbeat_timeout' => env('GATEWAY_WORKER_HEARTBEAT_TIMEOUT', 60),
];
