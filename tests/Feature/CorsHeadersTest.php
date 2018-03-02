<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\TestToken;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CorsHeadersTest extends TestCase
{
    use TestToken;

    /** @test */
    public function check_cors_headers_on_media_endpoint_options_request()
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
    public function check_missing_on_other_route()
    {
        $response = $this->get('/');
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
