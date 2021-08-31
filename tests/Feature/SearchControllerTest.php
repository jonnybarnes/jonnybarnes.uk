<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function searchPageReturnsResult(): void
    {
        Note::factory()->create([
            'note' => 'I love [duckduckgo.com](https://duckduckgo.com)',
        ]);
        $response = $this->get('/search?terms=love');
        $response->assertSee('duckduckgo.com');
    }
}
