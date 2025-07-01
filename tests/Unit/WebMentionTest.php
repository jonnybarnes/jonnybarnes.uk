<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Note;
use App\Models\WebMention;
use Codebird\Codebird;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
    public function create_photo_link_returns_cached_twitter_photo_links(): void
    {
        $webmention = new WebMention;
        $twitterURL = 'https://twitter.com/example';
        $expected = 'https://pbs.twimg.com/static_profile_link.jpg';
        Cache::put($twitterURL, $expected, 1);
        $this->assertEquals($expected, $webmention->createPhotoLink($twitterURL));
    }

    #[Test]
    public function create_photo_link_resolves_twitter_photo_links(): void
    {
        $info = (object) [
            'profile_image_url_https' => 'https://pbs.twimg.com/static_profile_link.jpg',
        ];
        $codebirdMock = $this->createPartialMock(Codebird::class, ['__call']);
        $codebirdMock->method('__call')
            ->with('users_show', $this->anything())
            ->willReturn($info);
        $this->app->instance(Codebird::class, $codebirdMock);

        Cache::shouldReceive('has')
            ->once()
            ->andReturn(false);
        Cache::shouldReceive('put')
            ->once()
            ->andReturn(true);

        $webmention = new WebMention;
        $twitterURL = 'https://twitter.com/example';
        $expected = 'https://pbs.twimg.com/static_profile_link.jpg';
        $this->assertEquals($expected, $webmention->createPhotoLink($twitterURL));
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
}
