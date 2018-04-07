<?php

namespace Tests\Feature;

use Tests\TestCase;

class CSPHeadersTest extends TestCase
{
    /** @test */
    public function check_csp_headers_test()
    {
        $response = $this->get('/');
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader('Report-To');
    }
}
