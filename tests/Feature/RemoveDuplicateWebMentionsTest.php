<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Note;
use App\Models\WebMention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveDuplicateWebMentionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_duplicates_keeping_the_oldest_webmention(): void
    {
        $note = Note::factory()->create();
        $otherNote = Note::factory()->create();
        $source = 'https://example.org/like/1';

        $original = $this->webmentionFor($note, $source);
        $this->webmentionFor($note, $source);
        $this->webmentionFor($note, $source);
        $sameSourceOtherNote = $this->webmentionFor($otherNote, $source);
        $unrelated = $this->webmentionFor($note, 'https://example.org/like/2');

        $this->artisan('webmentions:remove-duplicates')
            ->expectsOutput('Removed 2 duplicate webmentions.')
            ->assertSuccessful();

        $this->assertSame(1, WebMention::where('source', $source)->whereMorphedTo('commentable', $note)->count());
        $this->assertNotNull($original->fresh());
        $this->assertNotNull($sameSourceOtherNote->fresh());
        $this->assertNotNull($unrelated->fresh());
    }

    #[Test]
    public function dry_run_reports_duplicates_without_deleting_them(): void
    {
        $note = Note::factory()->create();
        $source = 'https://example.org/like/1';
        $this->webmentionFor($note, $source);
        $this->webmentionFor($note, $source);

        $this->artisan('webmentions:remove-duplicates', ['--dry-run' => true])
            ->expectsOutput('Found 1 duplicate webmentions (dry run, nothing deleted).')
            ->assertSuccessful();

        $this->assertSame(2, WebMention::where('source', $source)->count());
    }

    private function webmentionFor(Note $note, string $source): WebMention
    {
        return WebMention::factory()->create([
            'source' => $source,
            'target' => $note->uri,
            'type' => 'like-of',
            'commentable_id' => $note->id,
            'commentable_type' => Note::class,
        ]);
    }
}
