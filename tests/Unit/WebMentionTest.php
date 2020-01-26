<?php

namespace Tests\Unit;

use Codebird\Codebird;
use Tests\TestCase;
use App\Models\WebMention;
use Thujohn\Twitter\Facades\Twitter;
use Illuminate\Support\Facades\Cache;

class WebMentionTest extends TestCase
{
    public function test_commentable_method()
    {
        $webmention = WebMention::find(1);
        $this->assertInstanceOf('App\Models\Note', $webmention->commentable);
    }
    public function test_published_attribute_when_no_relavent_mf2()
    {
        $webmention = new WebMention();
        $updated_at = carbon()->now();
        $webmention->updated_at = $updated_at;
        $this->assertEquals($updated_at->toDayDateTimeString(), $webmention->published);
    }

    public function test_published_attribute_when_error_parsing_mf2()
    {
        $webmention = new WebMention();
        $updated_at = carbon()->now();
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

    /**
     * Test a correct profile link is formed from a generic URL.
     *
     * @return void
     */
    public function test_create_photo_link_with_non_cached_image()
    {
        $webmention = new WebMention();
        $homepage = 'https://example.org/profile.png';
        $expected = 'https://example.org/profile.png';
        $this->assertEquals($expected, $webmention->createPhotoLink($homepage));
    }

    /**
     * Test a correct profile link is formed from a generic URL (cached).
     *
     * @return void
     */
    public function test_create_photo_link_with_cached_image()
    {
        $webmention = new WebMention();
        $homepage = 'https://aaronparecki.com/profile.png';
        $expected = '/assets/profile-images/aaronparecki.com/image';
        $this->assertEquals($expected, $webmention->createPhotoLink($homepage));
    }

    /**
     * Test a correct profile link is formed from a twitter URL.
     *
     * @return void
     */
    public function test_create_photo_link_with_twimg_profile_image_url()
    {
        $webmention = new WebMention();
        $twitterProfileImage = 'http://pbs.twimg.com/1234';
        $expected = 'https://pbs.twimg.com/1234';
        $this->assertEquals($expected, $webmention->createPhotoLink($twitterProfileImage));
    }

    /**
     * Test `null` is returned for a twitter profile.
     *
     * @return void
     */
    public function test_create_photo_link_with_cached_twitter_url()
    {
        $webmention = new WebMention();
        $twitterURL = 'https://twitter.com/example';
        $expected = 'https://pbs.twimg.com/static_profile_link.jpg';
        Cache::put($twitterURL, $expected, 1);
        $this->assertEquals($expected, $webmention->createPhotoLink($twitterURL));
    }

    public function test_create_photo_link_with_noncached_twitter_url()
    {
        $info = new \stdClass();
        $info->profile_image_url_https = 'https://pbs.twimg.com/static_profile_link.jpg';
        $codebirdMock = $this->getMockBuilder(Codebird::class)
            ->addMethods(['users_show'])
            ->getMock();
        $codebirdMock->method('users_show')
            ->willReturn($info);
        $this->app->instance(Codebird::class, $codebirdMock);

        Cache::shouldReceive('has')
                    ->once()
                    ->andReturn(false);
        Cache::shouldReceive('put')
                    ->once()
                    ->andReturn(true);

        $webmention = new WebMention();
        $twitterURL = 'https://twitter.com/example';
        $expected = 'https://pbs.twimg.com/static_profile_link.jpg';
        $this->assertEquals($expected, $webmention->createPhotoLink($twitterURL));
    }

    public function test_get_reply_attribute_returns_null()
    {
        $webmention = new WebMention();
        $this->assertNull($webmention->reply);
    }

    public function test_get_reply_attribute_with_mf2_without_html_returns_null()
    {
        $webmention = new WebMention();
        $webmention->mf2 = json_encode(['no_html' => 'found_here']);
        $this->assertNull($webmention->reply);
    }
}
