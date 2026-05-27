<?php

namespace App\Aspects;

use Illuminate\Support\Facades\Log;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

class LoggingInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        $startedAt = microtime(true);
        $target = $this->targetName($invocation);
        $method = $invocation->getMethod()->getName();
        $logged = $invocation->getMethod()->getAnnotation(Logged::class);

        Log::info('aop.method.started', [
            'target' => $target,
            'method' => $method,
            'timestamp' => now()->toIso8601String(),
        ]);

        if ($logged instanceof Logged) {
            Log::log($logged->level, 'aop.decorated.started', [
                'target' => $target,
                'method' => $method,
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        $result = $invocation->proceed();

        Log::info('aop.method.finished', [
            'target' => $target,
            'method' => $method,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'timestamp' => now()->toIso8601String(),
        ]);

        if ($logged instanceof Logged) {
            Log::log($logged->level, 'aop.decorated.finished', [
                'target' => $target,
                'method' => $method,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return $result;
    }

    private function targetName(MethodInvocation $invocation): string
    {
        $target = $invocation->getThis();

        return is_object($target) ? $target::class : (string) $target;
    }
}
