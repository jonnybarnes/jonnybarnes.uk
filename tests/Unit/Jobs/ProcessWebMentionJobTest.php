<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Models\Note;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use App\Jobs\SaveProfileImage;
use App\Jobs\ProcessWebMention;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Support\Facades\Queue;
use Jonnybarnes\WebmentionsParser\Parser;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProcessWebMentionJobTest extends TestCase
{
    use DatabaseTransactions;

    public function tearDown()
    {
        if (file_exists(storage_path() . '/HTML/https/example.org/mention/1/index.html')) {
            unlink(storage_path() . '/HTML/https/example.org/mention/1/index.html');
            rmdir(storage_path() . '/HTML/https/example.org/mention/1');
            rmdir(storage_path() . '/HTML/https/example.org/mention');
            rmdir(storage_path() . '/HTML/https/example.org');
            rmdir(storage_path() . '/HTML/https');
        }
        if (file_exists(storage_path() . '/HTML/https/aaronpk.localhost/reply/1')) {
            unlink(storage_path() . '/HTML/https/aaronpk.localhost/reply/1');
            rmdir(storage_path() . '/HTML/https/aaronpk.localhost/reply');
            rmdir(storage_path() . '/HTML/https/aaronpk.localhost');
            rmdir(storage_path() . '/HTML/https');
        }
        parent::tearDown();
    }

    /**
     * @expectedException \App\Exceptions\RemoteContentNotFoundException
     */
    public function test_for_exception_from_failure_to_get_webmention()
    {
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

    public function test_a_new_webmention_gets_saved()
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

    public function test_existing_webmention_gets_updated()
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
            'mf2' => '{"rels": [], "items": [{"type": ["h-entry"], "properties": {"content": [{"html": "Updated reply", "value": "Updated reply"}], "in-reply-to": ["' . config('app.url') . '/notes/E"]}}], "rel-urls": []}',
        ]);
    }
}
