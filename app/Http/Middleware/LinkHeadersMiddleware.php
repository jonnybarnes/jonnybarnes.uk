<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LinkHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->header('Link', '<https://indieauth.com/auth>; rel="authorization_endpoint"', false);
        $response->header('Link', '<' . config('app.url') . '/api/token>; rel="token_endpoint"', false);
        $response->header('Link', '<' . config('app.url') . '/api/post>; rel="micropub"', false);
        $response->header('Link', '<' . config('app.url') . '/webmention>; rel="webmention"', false);

        return $response;
    }
}
