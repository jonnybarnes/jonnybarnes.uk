<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HeaderLinkTest extends TestCase
{
    #[Test]
    public function itShouldSeeTheIndiewebRelatedLinkHeaders(): void
    {
        $response = $this->get('/');

        $linkHeaders = $response->headers->allPreserveCaseWithoutCookies()['Link'];

        $this->assertSame('<' . config('app.url') . '/.well-known/indieauth-server>; rel="indieauth-metadata"', $linkHeaders[0]);
        $this->assertSame('<' . config('app.url') . '/auth>; rel="authorization_endpoint"', $linkHeaders[1]);
        $this->assertSame('<' . config('app.url') . '/token>; rel="token_endpoint"', $linkHeaders[2]);
        $this->assertSame('<' . config('app.url') . '/api/post>; rel="micropub"', $linkHeaders[3]);
        $this->assertSame('<' . config('app.url') . '/webmention>; rel="webmention"', $linkHeaders[4]);
    }
}
