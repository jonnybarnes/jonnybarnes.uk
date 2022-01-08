<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class CSPHeadersTest extends TestCase
{
    /** @test */
    public function checkCspHeadersArePresent(): void
    {
        $response = $this->get('/blog');
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader('Report-To');
    }
}
