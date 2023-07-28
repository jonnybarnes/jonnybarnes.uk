<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalhostSessionMiddleware
{
    /**
     * Whilst we are developing locally, automatically log in as
     * `['me' => config('app.url')]` as I can’t manually log in as
     * a .localhost domain.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') !== 'production') {
            session(['me' => config('app.url')]);
        }

        return $next($request);
    }
}
