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
            } else {
                $data = [
                    'me' => config('app.url'),
                    'client_id' => route('micropub-client'),
                    'scope' => 'post',
                ];
                $tokenService = new \App\Services\TokenService();
                session(['token' => $tokenService->getNewToken($data)]);
            }
        }

        return $next($request);
    }
}
