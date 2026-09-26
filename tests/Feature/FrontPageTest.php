<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrontPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function front_page_loads_all_content(): void
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

    #[Test]
    public function front_page_does_not_show_draft_articles(): void
    {
        Article::factory()->create(['title' => 'Published Article']);
        Article::factory()->create(['title' => 'Draft Article', 'published' => 0]);

        $this->get('/')
            ->assertSee('Published Article')
            ->assertDontSee('Draft Article');
    }
}
