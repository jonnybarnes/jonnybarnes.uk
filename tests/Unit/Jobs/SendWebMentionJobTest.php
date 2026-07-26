<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendWebMentions;
use App\Models\Note;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendWebMentionJobTest extends TestCase
{
    #[Test]
    public function discover_webmention_endpoint_on_own_domain(): void
    {
        $note = new Note;
        $job = new SendWebMentions($note);
        $this->assertNull($job->discoverWebmentionEndpoint(config('app.url')));
        $this->assertNull($job->discoverWebmentionEndpoint('/notes/tagged/test'));
    }

    #[Test]
    public function discover_webmention_endpoint_from_header_links(): void
    {
        $url = 'https://example.org/webmention';
        Http::fake([
            '*' => Http::response('', 200, ['Link' => '<'.$url.'>; rel="webmention"']),
        ]);

        $job = new SendWebMentions(new Note);
        $this->assertEquals($url, $job->discoverWebmentionEndpoint('https://example.org'));
    }

    #[Test]
    public function discover_webmention_endpoint_from_html_link_tags(): void
    {
        $html = '<link rel="webmention" href="https://example.org/webmention">';
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $job = new SendWebMentions(new Note);
        $this->assertEquals(
            'https://example.org/webmention',
            $job->discoverWebmentionEndpoint('https://example.org')
        );
    }

    #[Test]
    public function discover_webmention_endpoint_from_legacy_html_markup(): void
    {
        $html = '<link rel="http://webmention.org/" href="https://example.org/webmention">';
        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $job = new SendWebMentions(new Note);
        $this->assertEquals(
            'https://example.org/webmention',
            $job->discoverWebmentionEndpoint('https://example.org')
        );
    }

    #[Test]
    public function ensure_empty_note_does_not_trigger_any_actions(): void
    {
        $job = new SendWebMentions(new Note);
        $this->assertNull($job->handle());
    }

    #[Test]
    public function we_resolve_relative_uris(): void
    {
        $uri = '/blog/post';
        $base = 'https://example.org/';
        $job = new SendWebMentions(new Note);
        $this->assertEquals('https://example.org/blog/post', $job->resolveUri($uri, $base));
    }

    #[Test]
    public function we_send_a_webmention_for_a_note(): void
    {
        $html = '<link rel="http://webmention.org/" href="https://example.org/webmention">';
        Http::fake([
            'example.org/webmention' => Http::response('', 202),
            '*' => Http::response($html, 200),
        ]);

        $note = new Note;
        $note->note = 'Hi [Aaron](https://aaronparecki.com)';
        $note->save();
        $job = new SendWebMentions($note);
        $job->handle();
        $this->assertTrue(true);
    }

    #[Test]
    public function links_in_notes_can_not_support_webmentions(): void
    {
        Http::fake([
            // URLs with commas currently break the parse function I’m using
            '*' => Http::response('', 200, ['Link' => '<https://example.org/foo,bar>; rel="preconnect"']),
        ]);

        $job = new SendWebMentions(new Note);
        $this->assertNull($job->discoverWebmentionEndpoint('https://example.org'));
    }
}
