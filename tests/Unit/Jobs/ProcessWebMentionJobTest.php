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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Jonnybarnes\WebmentionsParser\Parser;
use Tests\TestCase;

class ProcessWebMentionJobTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        $fs = new FileSystem();
        if ($fs->exists(storage_path() . '/HTML/https')) {
            $fs->deleteDirectory(storage_path() . '/HTML/https');
        }
        parent::tearDown();
    }

    /** @test */
    public function failureGettingWebmentionThrowsAnException(): void
    {
        $this->expectException(RemoteContentNotFoundException::class);

        $parser = new Parser();
        $mock = new MockHandler([
            new Response(404),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::find(1);
        $source = 'https://example.org/mention/1/';

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, $client);
    }

    /** @test */
    public function newWebmentionGetsSavedByJob(): void
    {
        Queue::fake();

        $parser = new Parser();

        $html = <<<HTML
        <div class="h-entry">
            I liked <a class="u-like-of" href="/notes/1">a note</a>.
        </div>
        HTML;
        $html = str_replace('href="', 'href="' . config('app.url'), $html);
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::find(1);
        $source = 'https://example.org/mention/1/';

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, $client);

        Queue::assertPushed(SaveProfileImage::class);
        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
            'type' => 'like-of',
        ]);
    }

    /** @test */
    public function existingWebmentionGetsUpdatedByJob(): void
    {
        Queue::fake();

        $parser = new Parser();

        $html = <<<HTML
        <div class="h-entry">
            <p>In reply to <a class="u-in-reply-to" href="/notes/E">a note</a></p>
            <div class="e-content">Updated reply</div>
        </div>
        HTML;
        $html = str_replace('href="', 'href="' . config('app.url'), $html);
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::find(14);
        $source = 'https://aaronpk.localhost/reply/1';

        $job = new ProcessWebMention($note, $source);
        $job->handle($parser, $client);

        Queue::assertPushed(SaveProfileImage::class);
        $this->assertDatabaseHas('webmentions', [
            'source' => $source,
            'type' => 'in-reply-to',
            // phpcs:ignore Generic.Files.LineLength.TooLong
            'mf2' => '{"rels": [], "items": [{"type": ["h-entry"], "properties": {"content": [{"html": "Updated reply", "value": "Updated reply"}], "in-reply-to": ["' . config('app.url') . '/notes/E"]}}], "rel-urls": []}',
        ]);
    }

    /** @test */
    public function webmentionReplyGetsDeletedWhenReplyToValueChanges(): void
    {
        $parser = new Parser();

        $html = <<<HTML
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

        $note = Note::find(14);
        $source = 'https://example.org/reply/1';
        $webmention = new WebMention();
        $webmention->source = $source;
        $webmention->target = config('app.url') . '/notes/E';
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

    /** @test */
    public function webmentionLikeGetsDeletedWhenLikeOfValueChanges(): void
    {
        $parser = new Parser();

        $html = <<<HTML
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

        $note = Note::find(14);
        $source = 'https://example.org/reply/1';
        $webmention = new WebMention();
        $webmention->source = $source;
        $webmention->target = config('app.url') . '/notes/E';
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

    /** @test */
    public function webmentionRepostGetsDeletedWhenRepostOfValueChanges(): void
    {
        $parser = new Parser();

        $html = <<<HTML
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

        $note = Note::find(14);
        $source = 'https://example.org/reply/1';
        $webmention = new WebMention();
        $webmention->source = $source;
        $webmention->target = config('app.url') . '/notes/E';
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
