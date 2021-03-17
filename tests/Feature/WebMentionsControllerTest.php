<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessWebMention;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebMentionsControllerTest extends TestCase
{
    /** @test */
    public function webmentionEndpointCanServeBrowserRequest(): void
    {
        $response = $this->get('/webmention');
        $response->assertViewIs('webmention-endpoint');
    }

    /**
     * Test webmentions without source and target are rejected.
     *
     * @test
     */
    public function webmentionsWithoutSourceAndTargetAreRejected(): void
    {
        $response = $this->call('POST', '/webmention', ['source' => 'https://example.org/post/123']);
        $response->assertStatus(400);
    }

    /**
     * Test invalid target gives a 400 response.
     *
     * In this case an invalid target is a URL that doesn’t exist on our domain.
     *
     * @test
     */
    public function invalidTargetReturnsErrorResponse(): void
    {
        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => config('app.url') . '/invalid/target'
        ]);
        $response->assertStatus(400);
    }

    /**
     * Test blog target gets a 501 response due to our not supporting it.
     *
     * @test
     */
    public function blogTargetReturns501Response(): void
    {
        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => config('app.url') . '/blog/target'
        ]);
        $response->assertStatus(501);
    }

    /**
     * Test that a non-existent note gives a 400 response.
     *
     * @test
     */
    public function nonexistentNoteReturnsErrorResponse(): void
    {
        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => config('app.url') . '/notes/ZZZZZ'
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function legitimateWebmentionTriggersProcesswebmentionJob(): void
    {
        Queue::fake();

        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => config('app.url') . '/notes/B'
        ]);
        $response->assertStatus(202);

        Queue::assertPushed(ProcessWebMention::class);
    }
}
