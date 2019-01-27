<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Media;

class MediaTest extends TestCase
{
    public function test_get_note_from_media()
    {
        $media = Media::find(1);
        $note = $media->note;
        $this->assertInstanceOf('App\Models\Note', $note);
    }

    public function test_media_absolute_url_returned_unmodified()
    {
        $absoluteUrl = 'https://instagram-cdn.com/image/uuid';
        $media = new Media();
        $media->path = $absoluteUrl;

        $this->assertEquals($absoluteUrl, $media->url);
    }
}
