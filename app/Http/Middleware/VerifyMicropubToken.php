<?php

namespace App\Http\Middleware;

use Closure;

class VerifyMicropubToken
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
        if ($request->bearerToken() === null) {
            return response()->json([
                'response' => 'error',
                'error' => 'unauthorized',
                'error_description' => 'No access token was provided in the request',
            ], 401);
        }

        return $next($request);
    }
}
