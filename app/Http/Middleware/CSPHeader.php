<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class CSPHeader
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (App::environment('local', 'development')) {
            return $next($request);
        }

        // headers have to be single-line strings,
        // so we concat multiple lines
        // phpcs:disable Generic.Files.LineLength.TooLong
        return $next($request)
            ->header(
                'Content-Security-Policy',
                "default-src 'self'; " .
                "style-src 'self' cloud.typography.com jonnybarnes.uk; " .
                "img-src 'self' data: blob: https://pbs.twimg.com https://jbuk-media.s3-eu-west-1.amazonaws.com https://jbuk-media-dev.s3-eu-west-1.amazonaws.com https://secure.gravatar.com https://graph.facebook.com *.fbcdn.net https://*.cdninstagram.com https://*.4sqi.net https://upload.wikimedia.org; " .
                "font-src 'self' data:; " .
                "frame-src 'self' https://www.youtube.com blob:; " .
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
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
