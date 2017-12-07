<?php

namespace Tests\Unit;

use App\Media;
use Tests\TestCase;

class MediaTest extends TestCase
{
    public function test_get_note_from_media()
    {
        $media = Media::find(1);
        $note = $media->note;
        $this->assertInstanceOf('App\Note', $note);
    }
}
