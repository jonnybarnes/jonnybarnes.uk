<?php

namespace App\Http\Middleware;

use Closure;

class CorsHeaders
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
        $response = $next($request);
        if ($request->path() === 'api/media') {
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'OPTIONS, POST');
            $response->header(
                'Access-Control-Allow-Headers',
                'Authorization, Content-Type, DNT, X-CSRF-TOKEN, X-REQUESTED-WITH'
            );
            $response->header('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
