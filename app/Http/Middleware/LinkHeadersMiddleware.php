<?php

namespace App\Http\Middleware;

use Closure;

class LinkHeadersMiddleware
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
        $response->header('Link', '<https://indieauth.com/auth>; rel="authorization_endpoint"', false);
        $response->header('Link', config('app.url') . '/api/token>; rel="token_endpoint"', false);
        $response->header('Link', config('app.url') . '/api/post>; rel="micropub"', false);
        $response->header('Link', config('app.url') . '/webmention>; rel="webmention"', false);

        return $response;
    }
}
