<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tag;
use Tests\TestCase;

class TagsTest extends TestCase
{
    /** @test */
    public function canGetAssociatedNotes(): void
    {
        $tag = Tag::find(1); // should be beer tag
        $this->assertCount(1, $tag->notes);
    }

    /** @test */
    public function canGetAssociatedBookmarks(): void
    {
        $tag = Tag::find(5); //should be first random tag for bookmarks
        $this->assertCount(1, $tag->bookmarks);
    }
}
