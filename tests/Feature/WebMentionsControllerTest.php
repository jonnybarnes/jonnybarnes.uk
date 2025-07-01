<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessWebMention;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebMentionsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function webmention_endpoint_can_serve_browser_request(): void
    {
        $response = $this->get('/webmention');
        $response->assertViewIs('webmention-endpoint');
    }

    /**
     * Test webmentions without source and target are rejected.
     */
    #[Test]
    public function webmentions_without_source_and_target_are_rejected(): void
    {
        $response = $this->call('POST', '/webmention', ['source' => 'https://example.org/post/123']);
        $response->assertStatus(400);
    }

    /**
     * Test invalid target gives a 400 response.
     *
     * In this case an invalid target is a URL that doesn’t exist on our domain.
     */
    #[Test]
    public function invalid_target_returns_error_response(): void
    {
        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => config('app.url') . '/invalid/target',
        ]);
        $response->assertStatus(400);
    }

    /**
     * Test blog target gets a 501 response due to our not supporting it.
     */
    #[Test]
    public function blog_target_returns501_response(): void
    {
        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => config('app.url') . '/blog/target',
        ]);
        $response->assertStatus(501);
    }

    /**
     * Test that a non-existent note gives a 400 response.
     */
    #[Test]
    public function nonexistent_note_returns_error_response(): void
    {
        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => config('app.url') . '/notes/ZZZZZ',
        ]);
        $response->assertStatus(400);
    }

    #[Test]
    public function legitimate_webmention_triggers_process_webmention_job(): void
    {
        Queue::fake();

        $note = Note::factory()->create();

        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => $note->uri,
        ]);
        $response->assertStatus(202);

        Queue::assertPushed(ProcessWebMention::class);
    }
}
