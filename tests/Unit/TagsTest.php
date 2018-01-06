<?php

namespace Tests\Unit;

use App\Models\Tag;
use Tests\TestCase;

class TagsTest extends TestCase
{
    public function test_notes_method()
    {
        $tag = Tag::find(1); // should be beer tag
        $this->assertEquals(1, count($tag->notes));
    }

    public function test_bookmarks_method()
    {
        $tag = Tag::find(5); //should be first random tag for bookmarks
        $this->assertEquals(1, count($tag->bookmarks));
    }
}
