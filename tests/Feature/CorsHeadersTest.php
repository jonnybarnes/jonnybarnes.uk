<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\TestToken;

class CorsHeadersTest extends TestCase
{
    use TestToken;

    #[Test]
    public function check_cors_headers_on_media_endpoint(): void
    {
        $response = $this->call(
            'OPTIONS',
            '/api/media',
            [],
            [],
            [],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }

    #[Test]
    public function check_for_no_cors_header_on_non_media_endpoint_links(): void
    {
        $response = $this->get('/blog');
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
