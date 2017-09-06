<?php

namespace Tests\Feature;

use Tests\TestCase;

class FeedsTest extends TestCase
{
    /**
     * Test the blog RSS feed.
     *
     * @return void
     */
    public function test_blog_rss_feed()
    {
        $response = $this->get('/blog/feed.rss');
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Test the notes RSS feed.
     *
     * @return void
     */
    public function test_notes_rss_feed()
    {
        $response = $this->get('/notes/feed.rss');
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Test the blog RSS feed.
     *
     * @return void
     */
    public function test_blog_atom_feed()
    {
        $response = $this->get('/blog/feed.atom');
        $response->assertHeader('Content-Type', 'application/atom+xml; charset=utf-8');
    }

    /**
     * Test the notes RSS feed.
     *
     * @return void
     */
    public function test_notes_atom_feed()
    {
        $response = $this->get('/notes/feed.atom');
        $response->assertHeader('Content-Type', 'application/atom+xml; charset=utf-8');
    }

    /**
     * Test the blog JSON feed.
     *
     * @return void
     */
    public function test_blog_json_feed()
    {
        $response = $this->get('/blog/feed.json');
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test the notes JSON feed.
     *
     * @return void
     */
    public function test_notes_json_feed()
    {
        $response = $this->get('/notes/feed.json');
        $response->assertHeader('Content-Type', 'application/json');
    }
}
