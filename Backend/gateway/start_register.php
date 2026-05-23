<?php

use GatewayWorker\Register;
use Workerman\Worker;

require_once __DIR__.'/bootstrap.php';

$registerHost = config('gateway_worker.register_listen_host', '127.0.0.1');
$registerPort = (int) config('gateway_worker.register_port', 1236);

new Register("text://{$registerHost}:{$registerPort}");

if (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    Worker::runAll();
}
