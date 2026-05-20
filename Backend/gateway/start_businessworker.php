<?php

use GatewayWorker\BusinessWorker;
use Workerman\Worker;

require_once __DIR__.'/bootstrap.php';

$registerHost = config('gateway_worker.register_host', '127.0.0.1');
$registerPort = (int) config('gateway_worker.register_port', 1236);

$worker = new BusinessWorker();
$worker->name = 'PedwmBusinessWorker';
$worker->count = (int) config('gateway_worker.business_worker_processes', 4);
$worker->registerAddress = "{$registerHost}:{$registerPort}";
$worker->eventHandler = App\Gateway\GatewayWorkerEvents::class;

if (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    Worker::runAll();
}
