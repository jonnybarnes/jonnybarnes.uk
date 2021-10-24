<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Media;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function getTheNoteThatMediaInstanceBelongsTo(): void
    {
        $media = Media::factory()->for(Note::factory())->create();

        $this->assertInstanceOf(Note::class, $media->note);
    }

    /** @test */
    public function absoluteUrlsAreReturnedUnmodified(): void
    {
        $absoluteUrl = 'https://instagram-cdn.com/image/uuid';
        $media = new Media();
        $media->path = $absoluteUrl;

        $this->assertEquals($absoluteUrl, $media->url);
    }
}
