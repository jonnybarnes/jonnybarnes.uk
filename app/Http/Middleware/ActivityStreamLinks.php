<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActivityStreamLinks
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->path() === '/') {
            $response->header('Link', '<' . config('app.url') . '>; rel="application/activity+json"', false);
        }
        if ($request->is('notes/*')) {
            $response->header('Link', '<' . $request->url() . '>; rel="application/activity+json"', false);
        }

        return $response;
    }
}
