<?php

use Illuminate\Contracts\Console\Kernel;

require_once __DIR__.'/../vendor/autoload.php';

if (! isset($GLOBALS['pedwm_laravel_app'])) {
    $GLOBALS['pedwm_laravel_app'] = require __DIR__.'/../bootstrap/app.php';
    $GLOBALS['pedwm_laravel_app']->make(Kernel::class)->bootstrap();
}
