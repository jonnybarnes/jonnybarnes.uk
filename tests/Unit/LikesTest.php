<?php

namespace Tests\Unit;

use App\Like;
use Tests\TestCase;

class LikesTest extends TestCase
{
    public function test_setting_author_url()
    {
        $like = new Like();
        $like->author_url = 'https://joe.bloggs/';
        $this->assertEquals('https://joe.bloggs', $like->author_url);
    }
}
