<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    /** @test */
    public function searchPageReturnsResult(): void
    {
        $response = $this->get('/search?terms=love');
        $response->assertSee('duckduckgo.com');
    }
}
