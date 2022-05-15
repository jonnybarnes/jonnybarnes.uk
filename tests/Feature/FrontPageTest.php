<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function frontPageLoadsAllContent(): void
    {
        Note::factory()->create(['note' => 'Note 1']);
        Article::factory()->create(['title' => 'Article 1']);
        Bookmark::factory()->create(['url' => 'https://example.com']);
        Like::factory()->create([
            'url' => 'https://example.org/1',
            'content' => 'Like 1',
        ]);

        $this->get('/')
            ->assertSee('Note 1')
            ->assertSee('Article 1')
            ->assertSee('https://example.com')
            ->assertSee('Like 1');
    }
}
