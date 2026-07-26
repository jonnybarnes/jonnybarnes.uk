<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SaveProfileImage;
use Illuminate\Support\Facades\Http;
use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaveProfileImageJobTest extends TestCase
{
    protected function tearDown(): void
    {
        if (file_exists(public_path().'/assets/profile-images/example.org/image')) {
            unlink(public_path().'/assets/profile-images/example.org/image');
            rmdir(public_path().'/assets/profile-images/example.org');
        }
        parent::tearDown();
    }

    #[Test]
    public function authorship_algorithm_returns_null_on_exception(): void
    {
        $mf = ['items' => []];
        $authorship = $this->createMock(Authorship::class);
        $authorship->method('findAuthor')
            ->will($this->throwException(new AuthorshipParserException));
        $job = new SaveProfileImage($mf);

        $this->assertNull($job->handle($authorship));
    }

    #[Test]
    public function we_do_not_process_twitter_images(): void
    {
        $mf = ['items' => []];
        $author = [
            'properties' => [
                'photo' => ['https://pbs.twimg.com/abc.jpg'],
                'url' => ['https://twitter.com/profile'],
            ],
        ];
        $authorship = $this->createMock(Authorship::class);
        $authorship->method('findAuthor')
            ->willReturn($author);
        $job = new SaveProfileImage($mf);

        $this->assertNull($job->handle($authorship));
    }

    #[Test]
    public function remote_author_images_are_saved_locally(): void
    {
        Http::fake([
            '*' => Http::response('fake jpeg image', 200, ['Content-Type' => 'image/jpeg']),
        ]);
        $mf = ['items' => []];
        $author = [
            'properties' => [
                'photo' => ['https://example.org/profile.jpg'],
                'url' => ['https://example.org'],
            ],
        ];
        $authorship = $this->createMock(Authorship::class);
        $authorship->method('findAuthor')
            ->willReturn($author);

        $job = new SaveProfileImage($mf);
        $job->handle($authorship);
        $this->assertFileExists(public_path().'/assets/profile-images/example.org/image');
    }

    #[Test]
    public function local_default_author_image_is_used_as_fallback(): void
    {
        Http::fake([
            '*' => Http::response('', 404),
        ]);
        $mf = ['items' => []];
        $author = [
            'properties' => [
                'photo' => ['https://example.org/profile.jpg'],
                'url' => ['https://example.org'],
            ],
        ];
        $authorship = $this->createMock(Authorship::class);
        $authorship->method('findAuthor')
            ->willReturn($author);

        $job = new SaveProfileImage($mf);
        $job->handle($authorship);
        $this->assertFileEquals(
            public_path().'/assets/profile-images/default-image',
            public_path().'/assets/profile-images/example.org/image'
        );
    }

    #[Test]
    public function we_get_url_from_photo_object_if_alt_text_is_provided(): void
    {
        Http::fake([
            '*' => Http::response('fake jpeg image', 200, ['Content-Type' => 'image/jpeg']),
        ]);
        $mf = ['items' => []];
        $author = [
            'properties' => [
                'photo' => [[
                    'value' => 'https://example.org/profile.jpg',
                    'alt' => null,
                ]],
                'url' => ['https://example.org'],
            ],
        ];
        $authorship = $this->createMock(Authorship::class);
        $authorship->method('findAuthor')
            ->willReturn($author);

        $job = new SaveProfileImage($mf);
        $job->handle($authorship);
        $this->assertFileExists(public_path().'/assets/profile-images/example.org/image');
    }

    #[Test]
    public function use_first_url_if_multiple_homepages_are_provided(): void
    {
        Http::fake([
            '*' => Http::response('fake jpeg image', 200, ['Content-Type' => 'image/jpeg']),
        ]);
        $mf = ['items' => []];
        $author = [
            'properties' => [
                'photo' => [[
                    'value' => 'https://example.org/profile.jpg',
                    'alt' => null,
                ]],
                'url' => [[
                    'https://example.org',
                    'https://example.com',
                ]],
            ],
        ];
        $authorship = $this->createMock(Authorship::class);
        $authorship->method('findAuthor')
            ->willReturn($author);

        $job = new SaveProfileImage($mf);
        $job->handle($authorship);
        $this->assertFileExists(public_path().'/assets/profile-images/example.org/image');
    }
}
