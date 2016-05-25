<?php

namespace App\Tests;

use TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArticlesTest extends TestCase
{
    protected $appurl;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
    }

    /**
     * Test the `/blog` page returns the article, this
     * means the database is being hit.
     *
     * @return void
     */
    public function testArticlesPage()
    {
        $this->visit($this->appurl . '/blog')
             ->see('My New Blog');
    }

    /**
     * Test the `/blog/{year}` page returns the article, this
     * means the database is being hit.
     *
     * @return void
     */
    public function testArticlesYearPage()
    {
        $this->visit($this->appurl . '/blog/2016')
             ->see('My New Blog');
    }

    /**
     * Test the `/blog/{year}/{month}` page returns the article,
     * this means the database is being hit.
     *
     * @return void
     */
    public function testArticlesMonthPage()
    {
        $this->visit($this->appurl . '/blog/2016/01')
             ->see('My New Blog');
    }

    /**
     * Test a single article page.
     *
     * @return void
     */
    public function testSingleArticlePage()
    {
        $this->visit($this->appurl . '/blog/2016/01/my-new-blog')
             ->see('My New Blog');
    }

    /**
     * Test the RSS feed.
     *
     * @return void
     */
    public function testRSSFeed()
    {
        $response = $this->call('GET', $this->appurl . '/feed');

        $this->assertEquals('application/rss+xml', $response->headers->get('Content-Type'));
    }
}
