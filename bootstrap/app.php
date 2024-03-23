<?php

use App\Http\Middleware\CSPHeader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'api/token',
            'api/post',
            'api/media',
            'micropub/places',
            'webmention',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
