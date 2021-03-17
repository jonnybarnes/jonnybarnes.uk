<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Media;
use Tests\TestCase;

class MediaTest extends TestCase
{
    /** @test */
    public function getTheNoteThatMediaInstanceBelongsTo(): void
    {
        $media = Media::find(1);
        $note = $media->note;
        $this->assertInstanceOf('App\Models\Note', $note);
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
