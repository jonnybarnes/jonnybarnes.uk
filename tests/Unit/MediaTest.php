<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Media;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function getTheNoteThatMediaInstanceBelongsTo(): void
    {
        $media = Media::factory()->for(Note::factory())->create();

        $this->assertInstanceOf(Note::class, $media->note);
    }

    #[Test]
    public function absoluteUrlsAreReturnedUnmodified(): void
    {
        $absoluteUrl = 'https://instagram-cdn.com/image/uuid';
        $media = new Media;
        $media->path = $absoluteUrl;

        $this->assertEquals($absoluteUrl, $media->url);
    }
}
