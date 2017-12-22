<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Like;

class LikesTest extends TestCase
{
    public function test_setting_author_url()
    {
        $like = new Like();
        $like->author_url = 'https://joe.bloggs/';
        $this->assertEquals('https://joe.bloggs', $like->author_url);
    }
}
