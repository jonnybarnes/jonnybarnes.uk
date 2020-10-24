<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShortURLsControllerTest extends TestCase
{
    public function test_short_domain_redirects_to_long_domain()
    {
        $response = $this->get('http://' . config('app.shorturl'));
        $response->assertRedirect(config('app.url'));
    }

    public function test_short_domain_slashat_redirects_to_twitter()
    {
        $response = $this->get('http://' . config('app.shorturl') . '/@');
        $response->assertRedirect('https://twitter.com/jonnybarnes');
    }

    public function test_short_domain_slasht_redirects_to_long_domain_slash_notes()
    {
        $response = $this->get('http://' . config('app.shorturl') . '/t/E');
        $response->assertRedirect(config('app.url') . '/notes/E');
    }

    public function test_short_domain_slashb_redirects_to_long_domain_slash_blog()
    {
        $response = $this->get('http://' . config('app.shorturl') . '/b/1');
        $response->assertRedirect(config('app.url') . '/blog/s/1');
    }
}
