<?php

namespace App\Aspects;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ErrorLogged
{
    public function __construct(
        public readonly string $level = 'error',
    ) {}
}
