<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyMicropubToken
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
        if ($request->input('access_token')) {
            return $next($request);
        }

        if ($request->bearerToken()) {
            return $next($request->merge([
                'access_token' => $request->bearerToken(),
            ]));
        }

        return response()->json([
            'response' => 'error',
            'error' => 'unauthorized',
            'error_description' => 'No access token was provided in the request',
        ], 401);
    }
}
