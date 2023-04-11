<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Note;
use Tests\TestCase;

class SearchTest extends TestCase
{
    /** @test */
    public function searchEndpointReturnsResults(): void
    {
        Note::factory(10)->create();
        Note::Factory()->create(['note' => 'hello world']);

        $response = $this->get('/search?q=hello');

        $response->assertStatus(200);
        $response->assertViewIs('search');
        $response->assertViewHas('search');
        $response->assertViewHas('notes');
        $response->assertSee('hello world');
    }
}
