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
}
