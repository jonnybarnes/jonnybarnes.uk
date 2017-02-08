<?php

namespace App\Tests;

use BrowserKitTest;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WebMentionsTest extends BrowserKitTest
{
    protected $appurl;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
    }

    /**
     * Test webmentions without source and target are rejected.
     *
     * @return void
     */
    public function testWebmentionsWithoutSourceAndTargetAreRejected()
    {
        $this->call('POST', $this->appurl . '/webmention', ['source' => 'https://example.org/post/123']);
        $this->assertResponseStatus(400)
             ->see('You need both the target and source parameters');
    }

    /**
     * Test invalid target gets a 400 response.
     *
     * @return void
     */
    public function testInvalidTargetReturns400Response()
    {
        $this->call('POST', $this->appurl . '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => $this->appurl . '/invalid/target'
        ]);
        $this->assertResponseStatus(400)
             ->see('Invalid request');
    }

    /**
     * Test blog target gets a 501 response.
     *
     * @return void
     */
    public function testBlogpostTargetReturns501Response()
    {
        $this->call('POST', $this->appurl . '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => $this->appurl . '/blog/target'
        ]);
        $this->assertResponseStatus(501)
             ->see('I don’t accept webmentions for blog posts yet.');
    }

    /**
     * Test that a non-existant note gives a 400 response.
     *
     * @return void
     */
    public function testNonexistantNoteReturns400Response()
    {
        $this->call('POST', $this->appurl . '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => $this->appurl . '/notes/ZZZZZ'
        ]);
        $this->assertResponseStatus(400)
             ->see('This note doesn’t exist.');
    }

    /**
     * Test a legit webmention triggers the ProcessWebMention job.
     *
     * @return void
     */
    public function testLegitimateWebmnetionTriggersProcessWebMentionJob()
    {
        $this->expectsJobs(\App\Jobs\ProcessWebMention::class);
        $this->call('POST', $this->appurl . '/webmention', [
            'source' => 'https://example.org/post/123',
            'target' => $this->appurl . '/notes/B'
        ]);
        $this->assertResponseStatus(202)
             ->see('Webmention received, it will be processed shortly');
    }
}
