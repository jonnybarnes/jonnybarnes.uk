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
        $response->header('Link', '<' . route('indieauth.metadata') . '>; rel="indieauth-metadata"', false);
        $response->header('Link', '<' . route('indieauth.start') . '>; rel="authorization_endpoint"', false);
        $response->header('Link', '<' . route('indieauth.token') . '>; rel="token_endpoint"', false);
        $response->header('Link', '<' . route('micropub-endpoint') . '>; rel="micropub"', false);
        $response->header('Link', '<' . route('webmention-endpoint') . '>; rel="webmention"', false);

        return $response;
    }
}
