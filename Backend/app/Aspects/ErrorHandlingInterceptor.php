<?php

namespace App\Aspects;

use Illuminate\Support\Facades\Log;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Throwable;

class ErrorHandlingInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        try {
            return $invocation->proceed();
        } catch (Throwable $exception) {
            $target = $this->targetName($invocation);
            $method = $invocation->getMethod()->getName();
            $errorLogged = $invocation->getMethod()->getAnnotation(ErrorLogged::class);

            Log::error('aop.method.exception', [
                'target' => $target,
                'method' => $method,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($errorLogged instanceof ErrorLogged) {
                Log::log($errorLogged->level, 'aop.decorated.exception', [
                    'target' => $target,
                    'method' => $method,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'timestamp' => now()->toIso8601String(),
                ]);
            }

            throw $exception;
        }
    }

    private function targetName(MethodInvocation $invocation): string
    {
        $target = $invocation->getThis();

        return is_object($target) ? $target::class : (string) $target;
    }
}
