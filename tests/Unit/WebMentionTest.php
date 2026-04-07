<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Note;
use App\Models\WebMention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebMentionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function commentable_method_links_to_notes(): void
    {
        $note = Note::factory()->create();
        $webmention = WebMention::factory()->make([
            'commentable_id' => $note->id,
            'commentable_type' => Note::class,
        ]);
        $this->assertInstanceOf(Note::class, $webmention->commentable);
    }

    #[Test]
    public function published_attribute_uses_updated_at_when_no_relevant_mf2_data(): void
    {
        $webmention = new WebMention;
        $updated_at = Carbon::now();
        $webmention->updated_at = $updated_at;
        $this->assertEquals($updated_at->toDayDateTimeString(), $webmention->published);
    }

    #[Test]
    public function published_attribute_uses_updated_at_when_error_parsing_mf2_data(): void
    {
        $webmention = new WebMention;
        $updated_at = Carbon::now();
        $webmention->updated_at = $updated_at;
        $webmention->mf2 = json_encode([
            'items' => [[
                'properties' => [
                    'published' => [
                        'error',
                    ],
                ],
            ]],
        ]);
        $this->assertEquals($updated_at->toDayDateTimeString(), $webmention->published);
    }

    #[Test]
    public function create_photo_link_does_nothing_with_generic_url_and_no_locally_saved_image(): void
    {
        $webmention = new WebMention;
        $homepage = 'https://example.org/profile.png';
        $expected = 'https://example.org/profile.png';
        $this->assertEquals($expected, $webmention->createPhotoLink($homepage));
    }

    #[Test]
    public function create_photo_link_returns_locally_saved_image_url_if_it_exists(): void
    {
        $webmention = new WebMention;
        $homepage = 'https://aaronparecki.com/profile.png';
        $expected = '/assets/profile-images/aaronparecki.com/image';
        $this->assertEquals($expected, $webmention->createPhotoLink($homepage));
    }

    #[Test]
    public function create_photo_link_deals_with_special_case_of_direct_twitter_photo_links(): void
    {
        $webmention = new WebMention;
        $twitterProfileImage = 'http://pbs.twimg.com/1234';
        $expected = 'https://pbs.twimg.com/1234';
        $this->assertEquals($expected, $webmention->createPhotoLink($twitterProfileImage));
    }

    #[Test]
    public function get_reply_attribute_defaults_to_null(): void
    {
        $webmention = new WebMention;
        $this->assertNull($webmention->reply);
    }

    #[Test]
    public function get_reply_attribute_with_mf2_without_html_returns_null(): void
    {
        $webmention = new WebMention;
        $webmention->mf2 = json_encode(['no_html' => 'found_here']);
        $this->assertNull($webmention->reply);
    }

    #[Test]
    public function author_attribute_returns_null_when_mf2_is_null(): void
    {
        $webmention = new WebMention;
        $webmention->mf2 = null;
        $this->assertNull($webmention->author);
    }

    #[Test]
    public function author_attribute_returns_hcard_without_photo_key_when_no_photo_present(): void
    {
        $webmention = new WebMention;
        $webmention->mf2 = json_encode([
            'items' => [[
                'type' => ['h-entry'],
                'properties' => [
                    'author' => [[
                        'type' => ['h-card'],
                        'properties' => [
                            'name' => ['Test Person'],
                            'url' => ['https://example.com'],
                        ],
                    ]],
                    'content' => ['hello'],
                ],
            ]],
        ]);

        $hCard = $webmention->author;
        $this->assertNotNull($hCard);
        $this->assertArrayNotHasKey('photo', $hCard['properties'] ?? []);
    }
}
