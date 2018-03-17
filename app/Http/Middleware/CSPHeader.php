<?php

namespace App\Http\Middleware;

use Closure;

class CSPHeader
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
        // headers have to be single-line strings,
        // so we concat multiple lines
        //return $next($request);
        return $next($request)
            ->header(
                'Content-Security-Policy',
                str_replace("\\\n", '', "default-src 'self'; \
script-src 'self' 'unsafe-inline' 'unsafe-eval' \
https://api.mapbox.com \
https://analytics.jmb.lv \
blob:; \
style-src 'self' 'unsafe-inline' \
https://api.mapbox.com \
https://fonts.googleapis.com \
use.typekit.net \
p.typekit.net; \
img-src 'self' data: blob: \
https://pbs.twimg.com \
https://api.mapbox.com \
https://*.tiles.mapbox.com \
https://jbuk-media.s3-eu-west-1.amazonaws.com \
https://jbuk-media-dev.s3-eu-west-1.amazonaws.com \
https://secure.gravatar.com \
https://graph.facebook.com *.fbcdn.net \
https://*.cdninstagram.com \
analytics.jmb.lv \
https://*.4sqi.net \
https://upload.wikimedia.org \
p.typekit.net; \
font-src 'self' \
https://fonts.gstatic.com \
use.typekit.net \
fonts.typekit.net; \
connect-src 'self' \
https://api.mapbox.com \
https://*.tiles.mapbox.com \
performance.typekit.net \
data: blob:; \
worker-src 'self' blob:; \
frame-src 'self' https://www.youtube.com blob:; \
child-src 'self' blob:; \
upgrade-insecure-requests; \
block-all-mixed-content; \
report-to csp-endpoint; \
report-uri https://jonnybarnes.report-uri.io/r/default/csp/enforce;"
            ))
            ->header(
                'Report-To',
                '{' .
                    "'url': 'https://jonnybarnes.report-uri.io/r/default/csp/enforce', " .
                    "'group': 'csp-endpoint'," .
                    "'max-age': 10886400" .
                '}'
            );
    }
}
