<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Exceptions\RemoteContentNotFoundException;
use App\Jobs\ProcessWebMention;
use App\Jobs\SaveProfileImage;
use App\Models\Note;
use App\Models\WebMention;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\FileSystem\FileSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Jonnybarnes\WebmentionsParser\Parser;
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
        $mock = new MockHandler([
            new Response(404),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create();
        $source = 'https://example.org/mention/1/';

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, $client);
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
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create();
        $source = 'https://example.org/mention/1/';

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, $client);

        Queue::assertPushed(SaveProfileImage::class);
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
        ]);

        $html = <<<HTML
        <div class="h-entry">
            <p>In reply to <a class="u-in-reply-to" href="{$note->uri}">a note</a></p>
            <div class="e-content">Updated reply</div>
        </div>
        HTML;
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, $client);

        Queue::assertPushed(SaveProfileImage::class);
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
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create();
        $source = 'https://example.org/reply/1';
        $webmention = new WebMention;
        $webmention->source = $source;
        $webmention->target = config('app.url').'/notes/E';
        $webmention->type = 'in-reply-to';
        $webmention->save();

        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
        ]);

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, $client);

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
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create();
        $source = 'https://example.org/reply/1';
        $webmention = new WebMention;
        $webmention->source = $source;
        $webmention->target = config('app.url').'/notes/E';
        $webmention->type = 'like-of';
        $webmention->save();

        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
        ]);

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, $client);

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
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create();
        // Simulate a long brid.gy Bluesky source URL (well over 255 characters)
        $source = 'https://brid.gy/comment/bluesky/did:plc:n3jhgiq2ykctnpgzlm6p6b25/at%253A%252F%252Fdid%253Aplc%253An3jhgiq2ykctnpgzlm6p6b25%252Fapp.bsky.feed.post%252F3mfekjuhykb2k/at%253A%252F%252Fdid%253Aplc%253Afsfuwigo5juzdwp23hyianzg%252Fapp.bsky.feed.post%252F3mfemw4rrvs2t';

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, $client);

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
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create();
        $source = 'https://example.org/reply/1';
        $webmention = new WebMention;
        $webmention->source = $source;
        $webmention->target = config('app.url').'/notes/E';
        $webmention->type = 'repost-of';
        $webmention->save();

        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
        ]);

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, $client);

        $this->assertDatabaseMissing('webmentions', [
            'source' => $source,
        ]);
    }
}
