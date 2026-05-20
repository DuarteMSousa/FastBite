<?php

use Workerman\Worker;

require __DIR__.'/bootstrap.php';

require __DIR__.'/start_register.php';
require __DIR__.'/start_gateway.php';
require __DIR__.'/start_businessworker.php';

Worker::runAll();
