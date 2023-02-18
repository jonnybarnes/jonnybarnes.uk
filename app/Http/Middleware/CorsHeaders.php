<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request$request, Closure $next): Response
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
