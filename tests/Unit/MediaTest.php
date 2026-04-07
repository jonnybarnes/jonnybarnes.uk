<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Media;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function media_can_belong_to_multiple_notes(): void
    {
        $media = Media::factory()->create();
        $note1 = Note::factory()->create();
        $note2 = Note::factory()->create();

        $note1->media()->attach($media->id, ['alt_text' => 'Alt text for note 1']);
        $note2->media()->attach($media->id, ['alt_text' => 'Alt text for note 2']);

        $this->assertCount(2, $media->notes);
        $this->assertTrue($media->notes->contains($note1));
        $this->assertTrue($media->notes->contains($note2));
    }

    #[Test]
    public function absolute_urls_are_returned_unmodified(): void
    {
        $absoluteUrl = 'https://instagram-cdn.com/image/uuid';
        $media = new Media;
        $media->path = $absoluteUrl;

        $this->assertEquals($absoluteUrl, $media->url);
    }

    #[Test]
    public function local_paths_get_storage_url_prepended(): void
    {
        $media = new Media;
        $media->path = 'photo.jpg';

        $this->assertEquals(config('app.url').'/storage/photo.jpg', $media->url);
    }

    #[Test]
    public function medium_url_returns_resized_path(): void
    {
        $media = new Media;
        $media->path = 'photo.jpg';

        $this->assertEquals(config('app.url').'/storage/photo-medium.jpg', $media->mediumurl);
    }

    #[Test]
    public function small_url_returns_resized_path(): void
    {
        $media = new Media;
        $media->path = 'photo.jpg';

        $this->assertEquals(config('app.url').'/storage/photo-small.jpg', $media->smallurl);
    }

    #[Test]
    public function size_url_handles_dotted_basename(): void
    {
        $media = new Media;
        $media->path = 'file.name.png';

        $this->assertEquals(config('app.url').'/storage/file.name-medium.png', $media->mediumurl);
    }

    /**
     * @dataProvider mimeTypeProvider
     */
    #[DataProvider('mimeTypeProvider')]
    public function mimetype_returns_correct_mime_for_extension(string $path, string $expected): void
    {
        $media = new Media;
        $media->path = $path;

        $this->assertEquals($expected, $media->mimetype);
    }

    public static function mimeTypeProvider(): array
    {
        return [
            ['photo.gif', 'image/gif'],
            ['photo.jpeg', 'image/jpeg'],
            ['photo.jpg', 'image/jpeg'],
            ['photo.png', 'image/png'],
            ['photo.svg', 'image/svg+xml'],
            ['photo.tiff', 'image/tiff'],
            ['photo.webp', 'image/webp'],
            ['video.mp4', 'video/mp4'],
            ['video.mkv', 'video/mkv'],
            ['file.bin', 'application/octet-stream'],
        ];
    }
}
