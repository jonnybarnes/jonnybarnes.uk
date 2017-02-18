<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArticlesRSSTest extends TestCase
{
    /**
     * Test the RSS feed.
     *
     * @return void
     */
    public function test_rss_feed()
    {
        $response = $this->get('/feed');
        $response->assertHeader('Content-Type', 'application/rss+xml');
    }
}
