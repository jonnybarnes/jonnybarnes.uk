<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Tests\TestToken;

class CorsHeadersTest extends TestCase
{
    use TestToken;

    /** @test */
    public function checkCorsHeadersOnMediaEndpoint(): void
    {
        $response = $this->call(
            'OPTIONS',
            '/api/media',
            [],
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }

    /** @test */
    public function checkForNoCorsHeaderOnNonMediaEndpointLinks(): void
    {
        $response = $this->get('/blog');
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
