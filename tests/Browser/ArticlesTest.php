<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ArticlesTest extends DuskTestCase
{
    /**
     * Test the `/blog` page.
     *
     * @return void
     */
    public function test_articles_page()
    {
        $this->browse(function ($browser) {
            $browser->visit('/blog')
                    ->assertSee('My New Blog');
        });
    }

    /**
     * Test the `/blog` page with a year scoping results.
     *
     * @return void
     */
    public function test_articles_page_with_specified_year()
    {
        $this->browse(function ($browser) {
            $browser->visit('/blog/2016')
                    ->assertSee('My New Blog');
        });
    }

    /**
     * Test the `/blog` page with a year and month scoping results.
     *
     * @return void
     */
    public function test_articles_page_with_specified_year_and_month()
    {
        $this->browse(function ($browser) {
            $browser->visit('/blog/2016/01')
                    ->assertSee('My New Blog');
        });
    }

    /**
     * Test a single article page.
     *
     * @return void
     */
    public function test_single_article_page()
    {
        $this->browse(function ($browser) {
            $browser->visit('/blog/2016/01/my-new-blog')
                    ->assertSee('My New Blog');
        });
    }
}
