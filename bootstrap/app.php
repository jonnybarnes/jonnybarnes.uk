<?php

use App\Http\Middleware\LinkHeadersMiddleware;
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
        $middleware
            ->append(LinkHeadersMiddleware::class)
            ->validateCsrfTokens(except: [
                'auth',  // This is the IndieAuth auth endpoint
                'token', // This is the IndieAuth token endpoint
                'api/post',
                'api/media',
                'micropub/places',
                'webmention',
            ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
