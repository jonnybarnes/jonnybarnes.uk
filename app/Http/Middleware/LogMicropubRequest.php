<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogMicropubRequest
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $logger = new Logger('micropub');
        $logger->pushHandler(new StreamHandler(storage_path('logs/micropub.log')));
        $logger->debug('MicropubLog', $request->all());

        return $next($request);
    }
}
