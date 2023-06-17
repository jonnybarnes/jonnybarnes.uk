<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SaveProfileImage;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;
use Tests\TestCase;

class SaveProfileImageJobTest extends TestCase
{
    protected function tearDown(): void
    {
        if (file_exists(public_path() . '/assets/profile-images/example.org/image')) {
            unlink(public_path() . '/assets/profile-images/example.org/image');
            rmdir(public_path() . '/assets/profile-images/example.org');
        }
        parent::tearDown();
    }

    /** @test */
    public function authorshipAlgorithmReturnsNullOnException(): void
    {
        $mf = ['items' => []];
        $authorship = $this->createMock(Authorship::class);
        $authorship->method('findAuthor')
            ->will($this->throwException(new AuthorshipParserException()));
        $job = new SaveProfileImage($mf);

        $this->assertNull($job->handle($authorship));
    }

    /** @test */
    public function weDoNotProcessTwitterImages(): void
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

    /** @test */
    public function remoteAuthorImagesAreSavedLocally(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'image/jpeg'], 'fake jpeg image'),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
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
        $this->assertFileExists(public_path() . '/assets/profile-images/example.org/image');
    }

    /** @test */
    public function localDefaultAuthorImageIsUsedAsFallback(): void
    {
        $mock = new MockHandler([
            new Response(404),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
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
            public_path() . '/assets/profile-images/default-image',
            public_path() . '/assets/profile-images/example.org/image'
        );
    }

    /** @test */
    public function weGetUrlFromPhotoObjectIfAltTextIsProvided(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'image/jpeg'], 'fake jpeg image'),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
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
        $this->assertFileExists(public_path() . '/assets/profile-images/example.org/image');
    }

    /** @test */
    public function useFirstUrlIfMultipleHomepagesAreProvided(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'image/jpeg'], 'fake jpeg image'),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
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
        $this->assertFileExists(public_path() . '/assets/profile-images/example.org/image');
    }
}
