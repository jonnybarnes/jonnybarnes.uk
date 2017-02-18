<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\ProcessWebMention;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WebMentionsControllerTest extends TestCase
{
    /**
     * Test webmentions without source and target are rejected.
     *
     * @return void
     */
    public function test_webmentions_without_source_and_target_are_rejected()
    {
        $response = $this->call('POST', '/webmention', ['source' => 'https://example.org/post/123']);
        $response->assertStatus(400);
    }

    /**
     * Test invalid target gets a 400 response.
     *
     * @return void
     */
    public function test_invalid_target_returns_400_response()
    {
        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => config('app.url') . '/invalid/target'
        ]);
        $response->assertStatus(400);
    }

    /**
     * Test blog target gets a 501 response due to me not supporting it.
     *
     * @return void
     */
    public function test_blog_target_returns_501_response()
    {
        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => config('app.url') . '/blog/target'
        ]);
        $response->assertStatus(501);
    }

    /**
     * Test that a non-existant note gives a 400 response.
     *
     * @return void
     */
    public function test_nonexistant_note_returns_400_response()
    {
        $response = $this->call('POST', '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => config('app.url') . '/notes/ZZZZZ'
        ]);
        $response->assertStatus(400);
    }

    /**
     * Test a legit webmention triggers the ProcessWebMention job.
     *
     * @return void
     */
    public function test_legitimate_webmnetion_triggers_processwebmention_job()
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
