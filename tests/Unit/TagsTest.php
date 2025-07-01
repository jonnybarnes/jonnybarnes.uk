<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Bookmark;
use App\Models\Note;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_get_associated_notes(): void
    {
        $note = Note::factory()->create();
        $tag = Tag::factory()->create();
        $note->tags()->save($tag);
        $this->assertCount(1, $tag->notes);
    }

    #[Test]
    public function can_get_associated_bookmarks(): void
    {
        $bookmark = Bookmark::factory()->create();
        $tag = Tag::factory()->create();
        $bookmark->tags()->save($tag);
        $this->assertCount(1, $tag->bookmarks);
    }

    #[Test]
    #[DataProvider('tagsProvider')]
    public function can_normalize(string $input, string $expected): void
    {
        $this->assertSame($expected, Tag::normalize($input));
    }

    public static function tagsProvider(): array
    {
        return [
            ['test', 'test'],
            ['Test', 'test'],
            ['Tést', 'test'],
            ['MultiWord', 'multiword'],
        ];
    }
}
