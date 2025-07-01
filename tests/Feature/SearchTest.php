<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Note;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchTest extends TestCase
{
    #[Test]
    public function search_endpoint_returns_results(): void
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
