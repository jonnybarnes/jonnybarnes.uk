<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Bookmark;
use App\Models\Note;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function canGetAssociatedNotes(): void
    {
        $note = Note::factory()->create();
        $tag = Tag::factory()->create();
        $note->tags()->save($tag);
        $this->assertCount(1, $tag->notes);
    }

    /** @test */
    public function canGetAssociatedBookmarks(): void
    {
        $bookmark = Bookmark::factory()->create();
        $tag = Tag::factory()->create();
        $bookmark->tags()->save($tag);
        $this->assertCount(1, $tag->bookmarks);
    }

    /**
     * @test
     *
     * @dataProvider tagsProvider
     *
     * @param  string  $input
     * @param  string  $expected
     */
    public function canNormalize(string $input, string $expected): void
    {
        $this->assertSame($expected, Tag::normalize($input));
    }

    public function tagsProvider(): array
    {
        return [
            ['test', 'test'],
            ['Test', 'test'],
            ['Tést', 'test'],
            ['MultiWord', 'multiword'],
        ];
    }
}
