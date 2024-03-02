<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendWebMentions;
use App\Models\Note;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class SendWebMentionJobTest extends TestCase
{
    /** @test */
    public function discoverWebmentionEndpointOnOwnDomain(): void
    {
        $note = new Note();
        $job = new SendWebMentions($note);
        $this->assertNull($job->discoverWebmentionEndpoint(config('app.url')));
        $this->assertNull($job->discoverWebmentionEndpoint('/notes/tagged/test'));
    }

    /** @test */
    public function discoverWebmentionEndpointFromHeaderLinks(): void
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

    /** @test */
    public function discoverWebmentionEndpointFromHtmlLinkTags(): void
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

    /** @test */
    public function discoverWebmentionEndpointFromLegacyHtmlMarkup(): void
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

    /** @test */
    public function ensureEmptyNoteDoesNotTriggerAnyActions(): void
    {
        $job = new SendWebMentions(new Note());
        $this->assertNull($job->handle());
    }

    /** @test */
    public function weResolveRelativeUris(): void
    {
        $uri = '/blog/post';
        $base = 'https://example.org/';
        $job = new SendWebMentions(new Note());
        $this->assertEquals('https://example.org/blog/post', $job->resolveUri($uri, $base));
    }

    /** @test */
    public function weSendAWebmentionForANote(): void
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
        $job->handle();
        $this->assertTrue(true);
    }

    /** @test */
    public function linksInNotesCanNotSupportWebmentions(): void
    {
        $mock = new MockHandler([
            // URLs with commas currently break the parse function I’m using
            new Response(200, ['Link' => '<https://example.org/foo,bar>; rel="preconnect"']),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);

        $job = new SendWebMentions(new Note());
        $this->assertNull($job->discoverWebmentionEndpoint('https://example.org'));
    }
}
