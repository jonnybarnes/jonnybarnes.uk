<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Storage;

class DevTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (config('app.env') !== 'production') {
            session(['me' => config('app.url')]);
            if (Storage::exists('dev-token')) {
                session(['token' => Storage::get('dev-token')]);
            }
        }

        return $next($request);
    }
}
