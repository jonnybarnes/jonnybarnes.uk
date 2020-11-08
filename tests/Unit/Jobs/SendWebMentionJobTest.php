<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Models\Note;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use App\Jobs\SendWebMentions;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SendWebMentionJobTest extends TestCase
{
    public function test_dicover_endoint_method_on_self()
    {
        $note = new Note();
        $job = new SendWebMentions($note);
        $this->assertNull($job->discoverWebmentionEndpoint(config('app.url')));
        $this->assertNull($job->discoverWebmentionEndpoint('/notes/tagged/test'));
    }

    public function test_discover_endpoint_gets_link_from_headers()
    {
        $url = 'https://example.org/webmention';
        $mock = new MockHandler([
            new Response(200, ['Link' => '<' . $url . '>; rel="webmention"']),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);

        $job = new SendWebMentions(new Note());
        $this->assertEquals($url, $job->discoverWebmentionEndpoint('https://example.org'));
    }

    public function test_discover_endpoint_correctly_parses_html()
    {
        $html = '<link rel="webmention" href="https://example.org/webmention">';
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);

        $job = new SendWebMentions(new Note());
        $this->assertEquals(
            'https://example.org/webmention',
            $job->discoverWebmentionEndpoint('https://example.org')
        );
    }

    public function test_discover_endpoint_correctly_parses_html_legacy()
    {
        $html = '<link rel="http://webmention.org/" href="https://example.org/webmention">';
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);

        $job = new SendWebMentions(new Note());
        $this->assertEquals(
            'https://example.org/webmention',
            $job->discoverWebmentionEndpoint('https://example.org')
        );
    }

    public function test_empty_note_does_nothing()
    {
        $job = new SendWebMentions(new Note());
        $this->assertNull($job->handle());
    }

    public function test_resolve_uri()
    {
        $uri = '/blog/post';
        $base = 'https://example.org/';
        $job = new SendWebMentions(new Note());
        $this->assertEquals('https://example.org/blog/post', $job->resolveUri($uri, $base));
    }

    public function test_the_job()
    {
        $html = '<link rel="http://webmention.org/" href="https://example.org/webmention">';
        $mock = new MockHandler([
            new Response(200, [], $html),
            new Response(202),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);

        $note = new Note();
        $note->note = 'Hi [Aaron](https://aaronparecki.com)';
        $note->save();
        $job = new SendWebMentions($note);
        $this->assertNull($job->handle());
    }
}
