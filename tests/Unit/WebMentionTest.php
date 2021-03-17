<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\WebMention;
use Codebird\Codebird;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebMentionTest extends TestCase
{
    /** @test */
    public function commentableMethodLinksToNotes(): void
    {
        $webmention = WebMention::find(1);
        $this->assertInstanceOf('App\Models\Note', $webmention->commentable);
    }

    /** @test */
    public function publishedAttributeUsesUpdatedAtWhenNoRelevantMf2Data(): void
    {
        $webmention = new WebMention();
        $updated_at = Carbon::now();
        $webmention->updated_at = $updated_at;
        $this->assertEquals($updated_at->toDayDateTimeString(), $webmention->published);
    }

    /** @test */
    public function publishedAttributeUsesUpdatedAtWhenErrorParsingMf2Data(): void
    {
        $webmention = new WebMention();
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

    /** @test */
    public function createPhotoLinkDoesNothingWithGenericUrlAndNoLocallySavedImage(): void
    {
        $webmention = new WebMention();
        $homepage = 'https://example.org/profile.png';
        $expected = 'https://example.org/profile.png';
        $this->assertEquals($expected, $webmention->createPhotoLink($homepage));
    }

    /** @test */
    public function createPhotoLinkReturnsLocallySavedImageUrlIfItExists(): void
    {
        $webmention = new WebMention();
        $homepage = 'https://aaronparecki.com/profile.png';
        $expected = '/assets/profile-images/aaronparecki.com/image';
        $this->assertEquals($expected, $webmention->createPhotoLink($homepage));
    }

    /** @test */
    public function createPhotoLinkDealsWithSpecialCaseOfDirectTwitterPhotoLinks(): void
    {
        $webmention = new WebMention();
        $twitterProfileImage = 'http://pbs.twimg.com/1234';
        $expected = 'https://pbs.twimg.com/1234';
        $this->assertEquals($expected, $webmention->createPhotoLink($twitterProfileImage));
    }

    /** @test */
    public function createPhotoLinkReturnsCachedTwitterPhotoLinks(): void
    {
        $webmention = new WebMention();
        $twitterURL = 'https://twitter.com/example';
        $expected = 'https://pbs.twimg.com/static_profile_link.jpg';
        Cache::put($twitterURL, $expected, 1);
        $this->assertEquals($expected, $webmention->createPhotoLink($twitterURL));
    }

    /** @test */
    public function createPhotoLinkResolvesTwitterPhotoLinks(): void
    {
        $info = (object) [
            'profile_image_url_https' => 'https://pbs.twimg.com/static_profile_link.jpg',
        ];
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

    /** @test */
    public function getReplyAttributeDefaultsToNull(): void
    {
        $webmention = new WebMention();
        $this->assertNull($webmention->reply);
    }

    /** @test */
    public function getReplyAttributeWithMf2WithoutHtmlReturnsNull(): void
    {
        $webmention = new WebMention();
        $webmention->mf2 = json_encode(['no_html' => 'found_here']);
        $this->assertNull($webmention->reply);
    }
}
