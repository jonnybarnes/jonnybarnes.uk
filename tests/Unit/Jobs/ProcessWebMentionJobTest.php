<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Exceptions\RemoteContentNotFoundException;
use App\Jobs\NotifyBrrrOfWebMention;
use App\Jobs\ProcessWebMention;
use App\Jobs\SaveProfileImage;
use App\Models\Note;
use App\Models\WebMention;
use App\Services\WebMention\Authorship;
use App\Services\WebMention\Parser;
use Illuminate\FileSystem\FileSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessWebMentionJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $fs = new FileSystem;
        if ($fs->exists(storage_path().'/HTML/https')) {
            $fs->deleteDirectory(storage_path().'/HTML/https');
        }
        parent::tearDown();
    }

    #[Test]
    public function failure_getting_webmention_throws_an_exception(): void
    {
        $this->expectException(RemoteContentNotFoundException::class);

        $parser = new Parser;
        Http::fake([
            '*' => Http::response('', 404),
        ]);

        $note = Note::factory()->create();
        $source = 'https://example.org/mention/1/';

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, new Authorship);
    }

    #[Test]
    public function new_webmention_gets_saved_by_job(): void
    {
        Queue::fake();

        $parser = new Parser;

        $html = <<<'HTML'
        <div class="h-entry">
            I liked <a class="u-like-of" href="/notes/1">a note</a>.
        </div>
        HTML;
        $html = str_replace('href="', 'href="'.config('app.url'), $html);
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $note = Note::factory()->create();
        $source = 'https://example.org/mention/1/';

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, new Authorship);

        Queue::assertPushed(SaveProfileImage::class);
        Queue::assertPushed(NotifyBrrrOfWebMention::class);
        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
            'type' => 'like-of',
        ]);
    }

    #[Test]
    public function existing_webmention_gets_updated_by_job(): void
    {
        Queue::fake();

        $parser = new Parser;
        $note = Note::factory()->create();
        $source = 'https://aaronpk.localhost/reply/1';
        WebMention::factory()->create([
            'source' => $source,
            'target' => $note->uri,
            'commentable_id' => $note->id,
            'commentable_type' => Note::class,
        ]);

        $html = <<<HTML
        <div class="h-entry">
            <p>In reply to <a class="u-in-reply-to" href="{$note->uri}">a note</a></p>
            <div class="e-content">Updated reply</div>
        </div>
        HTML;
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, new Authorship);

        Queue::assertPushed(SaveProfileImage::class);
        Queue::assertNotPushed(NotifyBrrrOfWebMention::class);
        $this->assertNull(WebMention::where('source', $source)->first()->author);
        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
            'type' => 'in-reply-to',
            // phpcs:ignore Generic.Files.LineLength.TooLong
            'mf2' => '{"rels": [], "items": [{"type": ["h-entry"], "properties": {"content": [{"html": "Updated reply", "value": "Updated reply"}], "in-reply-to": ["'.$note->uri.'"]}}], "rel-urls": []}',
        ]);
    }

    #[Test]
    public function webmention_reply_gets_deleted_when_reply_to_value_changes(): void
    {
        $parser = new Parser;

        $html = <<<'HTML'
        <div class="h-entry">
            <p>In reply to <a class="u-in-reply-to" href="https://other.com/notes/E">a note</a></p>
            <div class="e-content">Replying to someone else</div>
        </div>
        HTML;
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $note = Note::factory()->create();
        $source = 'https://example.org/reply/1';
        $webmention = new WebMention;
        $webmention->source = $source;
        $webmention->target = config('app.url').'/notes/E';
        $webmention->type = 'in-reply-to';
        $webmention->commentable_id = $note->id;
        $webmention->commentable_type = Note::class;
        $webmention->save();

        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
        ]);

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, new Authorship);

        $this->assertDatabaseMissing('webmentions', [
            'source' => $source,
        ]);
    }

    #[Test]
    public function webmention_like_gets_deleted_when_like_of_value_changes(): void
    {
        $parser = new Parser;

        $html = <<<'HTML'
        <div class="h-entry">
            <p>In reply to <a class="u-like-of" href="https://other.com/notes/E">a note</a></p>
            <div class="e-content">I like someone else now</div>
        </div>
        HTML;
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $note = Note::factory()->create();
        $source = 'https://example.org/reply/1';
        $webmention = new WebMention;
        $webmention->source = $source;
        $webmention->target = config('app.url').'/notes/E';
        $webmention->type = 'like-of';
        $webmention->commentable_id = $note->id;
        $webmention->commentable_type = Note::class;
        $webmention->save();

        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
        ]);

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, new Authorship);

        $this->assertDatabaseMissing('webmentions', [
            'source' => $source,
        ]);
    }

    #[Test]
    public function webmention_with_long_source_url_gets_saved(): void
    {
        Queue::fake();

        $parser = new Parser;

        $html = <<<'HTML'
        <div class="h-entry">
            I liked <a class="u-like-of" href="/notes/1">a note</a>.
        </div>
        HTML;
        $html = str_replace('href="', 'href="'.config('app.url'), $html);
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $note = Note::factory()->create();
        // Simulate a long brid.gy Bluesky source URL (well over 255 characters)
        $source = 'https://brid.gy/comment/bluesky/did:plc:n3jhgiq2ykctnpgzlm6p6b25/at%253A%252F%252Fdid%253Aplc%253An3jhgiq2ykctnpgzlm6p6b25%252Fapp.bsky.feed.post%252F3mfekjuhykb2k/at%253A%252F%252Fdid%253Aplc%253Afsfuwigo5juzdwp23hyianzg%252Fapp.bsky.feed.post%252F3mfemw4rrvs2t';

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, new Authorship);

        Queue::assertPushed(NotifyBrrrOfWebMention::class);
        $this->assertGreaterThan(255, strlen($source));
        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
        ]);
    }

    #[Test]
    public function webmention_repost_gets_deleted_when_repost_of_value_changes(): void
    {
        $parser = new Parser;

        $html = <<<'HTML'
        <div class="h-entry">
            <p>In reply to <a class="u-repost-of" href="https://other.com/notes/E">a note</a></p>
            <div class="e-content">Reposting someone else</div>
        </div>
        HTML;
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $note = Note::factory()->create();
        $source = 'https://example.org/reply/1';
        $webmention = new WebMention;
        $webmention->source = $source;
        $webmention->target = config('app.url').'/notes/E';
        $webmention->type = 'repost-of';
        $webmention->commentable_id = $note->id;
        $webmention->commentable_type = Note::class;
        $webmention->save();

        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
        ]);

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, new Authorship);

        $this->assertDatabaseMissing('webmentions', [
            'source' => $source,
        ]);
    }

    #[Test]
    public function new_webmention_stores_resolved_author(): void
    {
        Queue::fake();

        $html = <<<'HTML'
        <div class="h-entry">
            <span class="p-author h-card">
                <img class="u-photo" src="https://example.org/photo.jpg" alt="">
                <a class="p-name u-url" href="https://example.org/">Example Person</a>
            </span>
            I liked <a class="u-like-of" href="/notes/1">a note</a>.
        </div>
        HTML;
        $html = str_replace('href="/notes', 'href="'.config('app.url').'/notes', $html);
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $note = Note::factory()->create();
        $source = 'https://example.org/mention/1/';

        $job = new ProcessWebMention($note, $source);
        $job->handle(new Parser, new Authorship);

        $webmention = WebMention::where('source', $source)->firstOrFail();
        $this->assertSame(['Example Person'], $webmention->author['properties']['name']);
        $this->assertSame(['https://example.org/'], $webmention->author['properties']['url']);
        Queue::assertPushed(SaveProfileImage::class, function (SaveProfileImage $job): bool {
            $author = (fn () => $this->author)->call($job);

            return ($author['properties']['name'] ?? null) === ['Example Person'];
        });
    }

    #[Test]
    public function new_webmention_stores_plain_name_author_as_hcard(): void
    {
        Queue::fake();

        $html = <<<'HTML'
        <div class="h-entry">
            <span class="p-author">Fred Bloggs</span>
            I liked <a class="u-like-of" href="/notes/1">a note</a>.
        </div>
        HTML;
        $html = str_replace('href="/notes', 'href="'.config('app.url').'/notes', $html);
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $note = Note::factory()->create();
        $source = 'https://example.org/mention/1/';

        $job = new ProcessWebMention($note, $source);
        $job->handle(new Parser, new Authorship);

        $webmention = WebMention::where('source', $source)->firstOrFail();
        $this->assertSame(
            ['type' => ['h-card'], 'properties' => ['name' => ['Fred Bloggs']]],
            $webmention->author
        );
    }

    #[Test]
    public function new_webmention_stores_null_author_when_author_page_cannot_be_fetched(): void
    {
        Queue::fake();

        $html = <<<'HTML'
        <div class="h-entry">
            <a class="u-author" href="https://example.org/about">me</a>
            I liked <a class="u-like-of" href="/notes/1">a note</a>.
        </div>
        HTML;
        $html = str_replace('href="/notes', 'href="'.config('app.url').'/notes', $html);
        Http::fake([
            'https://example.org/about' => Http::response('', 500),
            '*' => Http::response($html, 200),
        ]);

        $note = Note::factory()->create();
        $source = 'https://example.org/mention/1/';

        $job = new ProcessWebMention($note, $source);
        $job->handle(new Parser, new Authorship);

        $webmention = WebMention::where('source', $source)->firstOrFail();
        $this->assertNull($webmention->author);
        $this->assertNull($webmention->getRawOriginal('author'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function likeAndRepostTypes(): array
    {
        return [
            'like' => ['like-of'],
            'repost' => ['repost-of'],
        ];
    }

    #[Test]
    #[DataProvider('likeAndRepostTypes')]
    public function resent_like_or_repost_updates_the_existing_webmention(string $type): void
    {
        Queue::fake();

        $note = Note::factory()->create();
        $source = 'https://example.org/mention/1/';
        WebMention::factory()->create([
            'source' => $source,
            'target' => $note->uri,
            'type' => $type,
            'mf2' => null,
            'commentable_id' => $note->id,
            'commentable_type' => Note::class,
        ]);

        $html = <<<HTML
        <div class="h-entry">
            <span class="p-author">Fred Bloggs</span>
            <a class="u-{$type}" href="{$note->uri}">a note</a>
        </div>
        HTML;
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $job = new ProcessWebMention($note, $source);
        $job->handle(new Parser, new Authorship);

        $webmentions = WebMention::where('source', $source)->get();
        $this->assertCount(1, $webmentions);
        $this->assertSame($type, $webmentions->first()->type);
        $this->assertNotNull($webmentions->first()->mf2);
        $this->assertSame(['Fred Bloggs'], $webmentions->first()->author['properties']['name']);
        Queue::assertPushed(SaveProfileImage::class);
        Queue::assertNotPushed(NotifyBrrrOfWebMention::class);
    }

    #[Test]
    public function mention_of_a_second_note_from_the_same_source_creates_a_new_webmention(): void
    {
        Queue::fake();

        $firstNote = Note::factory()->create();
        $secondNote = Note::factory()->create();
        $source = 'https://example.org/mention/1/';
        $existing = WebMention::factory()->create([
            'source' => $source,
            'target' => $firstNote->uri,
            'type' => 'in-reply-to',
            'mf2' => null,
            'commentable_id' => $firstNote->id,
            'commentable_type' => Note::class,
        ]);

        $html = <<<HTML
        <div class="h-entry">
            In reply to <a class="u-in-reply-to" href="{$firstNote->uri}">one note</a>
            and <a class="u-in-reply-to" href="{$secondNote->uri}">another</a>.
        </div>
        HTML;
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $job = new ProcessWebMention($secondNote, $source);
        $job->handle(new Parser, new Authorship);

        // the first note's webmention is left untouched
        $this->assertNull($existing->fresh()->mf2);
        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
            'type' => 'in-reply-to',
            'commentable_id' => $secondNote->id,
        ]);
        $this->assertSame(2, WebMention::where('source', $source)->count());
        Queue::assertPushed(NotifyBrrrOfWebMention::class);
    }
}
