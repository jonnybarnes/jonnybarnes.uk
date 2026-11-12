<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\WebMention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResolveWebMentionAuthorsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_the_author_of_existing_webmentions(): void
    {
        Http::fake([
            'https://example.org/about' => Http::response('', 500),
        ]);
        $withHCard = WebMention::factory()->create([
            'mf2' => json_encode(['items' => [[
                'type' => ['h-entry'],
                'properties' => ['author' => [[
                    'type' => ['h-card'],
                    'properties' => ['name' => ['Fred Bloggs'], 'url' => ['https://fred.example/']],
                    'value' => 'Fred Bloggs',
                ]]],
            ]]]),
        ]);
        $withName = WebMention::factory()->create([
            'mf2' => json_encode(['items' => [[
                'type' => ['h-entry'],
                'properties' => ['author' => ['Jane Doe']],
            ]]]),
        ]);
        $withBrokenAuthorPage = WebMention::factory()->create([
            'mf2' => json_encode(['items' => [[
                'type' => ['h-entry'],
                'properties' => ['author' => ['https://example.org/about']],
            ]]]),
        ]);
        $withoutMf2 = WebMention::factory()->create(['mf2' => null]);

        $this->artisan('webmentions:resolve-authors')
            ->expectsOutput('Resolved 2 authors, 1 unknown.')
            ->assertSuccessful();

        $this->assertSame(['Fred Bloggs'], $withHCard->fresh()->author['properties']['name']);
        $this->assertSame(['Jane Doe'], $withName->fresh()->author['properties']['name']);
        $this->assertNull($withBrokenAuthorPage->fresh()->author);
        $this->assertNull($withoutMf2->fresh()->author);
    }
}
