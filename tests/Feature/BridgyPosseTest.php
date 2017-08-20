<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BridgyPosseTest extends TestCase
{
    public function test_bridgy_twitter_content()
    {
        $response = $this->get('/notes/C');

        $html = $response->content();
        $this->assertTrue(is_string(mb_stristr($html, 'p-bridgy-twitter-content')));
    }

    public function test_bridgy_facebook_content()
    {
        $response = $this->get('/notes/C');

        $html = $response->content();
        $this->assertTrue(is_string(mb_stristr($html, 'p-bridgy-facebook-content')));
    }
}
