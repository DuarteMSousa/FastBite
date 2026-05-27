<?php

namespace App\Aspects;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Logged
{
    public function __construct(
        public readonly string $level = 'info',
    ) {}
}
