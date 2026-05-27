<?php

use GatewayWorker\Gateway;
use App\Gateway\SocketServerEventType;
use Workerman\Worker;

require_once __DIR__.'/bootstrap.php';

$host = config('gateway_worker.websocket_host', '0.0.0.0');
$port = (int) config('gateway_worker.websocket_port', 8090);
$registerHost = config('gateway_worker.register_host', '127.0.0.1');
$registerPort = (int) config('gateway_worker.register_port', 1236);

$gateway = new Gateway("websocket://{$host}:{$port}");
$gateway->name = 'PedwmGateway';
$gateway->count = (int) config('gateway_worker.gateway_processes', 2);
$lanIp = config('gateway_worker.lan_ip', '127.0.0.1');
$resolvedLanIp = filter_var($lanIp, FILTER_VALIDATE_IP) ? $lanIp : gethostbyname($lanIp);
$gateway->lanIp = $resolvedLanIp !== $lanIp ? $resolvedLanIp : $lanIp;
$gateway->startPort = (int) config('gateway_worker.start_port', 2300);
$gateway->registerAddress = "{$registerHost}:{$registerPort}";
$gateway->pingInterval = (int) config('gateway_worker.heartbeat_interval', 30);
$gateway->pingNotResponseLimit = 2;
$gateway->pingData = json_encode(['type' => SocketServerEventType::GATEWAY_HEARTBEAT->value]);

if (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    Worker::runAll();
}
