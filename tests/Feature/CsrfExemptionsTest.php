<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsrfExemptionsTest extends TestCase
{
    /**
     * CSRF verification is short-circuited entirely while running the test
     * suite (see PreventRequestForgery::runningUnitTests()), so a normal
     * feature test hitting these routes would pass even if they were never
     * added to bootstrap/app.php's except list. Assert against the actual
     * configured exemptions instead.
     */
    #[Test]
    public function external_api_endpoints_are_exempt_from_csrf_verification(): void
    {
        $exemptions = $this->app->make(PreventRequestForgery::class)->getExcludedPaths();

        foreach (['auth', 'token', 'revocation', 'introspect', 'api/post', 'api/media', 'micropub/places', 'webmention'] as $path) {
            $this->assertContains($path, $exemptions);
        }
    }
}
