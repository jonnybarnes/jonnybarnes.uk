<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CSPHeader
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // headers have to be single-line strings,
        // so we concat multiple lines
        // phpcs:disable
        return $next($request)
            ->header(
                'Content-Security-Policy',
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://api.mapbox.com https://api.tiles.mapbox.com blob:; " .
                "style-src 'self' 'unsafe-inline' https://api.mapbox.com https://api.tiles.mapbox.com cloud.typography.com jonnybarnes.uk; " .
                "img-src 'self' data: blob: https://pbs.twimg.com https://api.mapbox.com https://*.tiles.mapbox.com https://jbuk-media.s3-eu-west-1.amazonaws.com https://jbuk-media-dev.s3-eu-west-1.amazonaws.com https://secure.gravatar.com https://graph.facebook.com *.fbcdn.net https://*.cdninstagram.com https://*.4sqi.net https://upload.wikimedia.org; " .
                "font-src 'self' data:; " .
                "connect-src 'self' https://api.mapbox.com https://*.tiles.mapbox.com https://events.mapbox.com data: blob:; " .
                "worker-src 'self' blob:; " .
                "frame-src 'self' https://www.youtube.com blob:; " .
                'child-src blob:; ' .
                'upgrade-insecure-requests; ' .
                'block-all-mixed-content; ' .
                'report-to csp-endpoint; ' .
                'report-uri https://jonnybarnes.report-uri.io/r/default/csp/enforce;'
            )->header(
                'Report-To',
                '{' .
                        "'url': 'https://jonnybarnes.report-uri.io/r/default/csp/enforce', " .
                        "'group': 'csp-endpoint', " .
                        "'max-age': 10886400" .
                '}'
            );
        // phpcs:enable
    }
}
