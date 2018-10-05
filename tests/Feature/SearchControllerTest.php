<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchControllerTest extends TestCase
{
    public function test_search()
    {
        $response = $this->get('/search?terms=love');
        $response->assertSee('duckduckgo.com');
    }
}
