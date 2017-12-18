<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use App\Jobs\SaveProfileImage;
use GuzzleHttp\Handler\MockHandler;
use Jonnybarnes\WebmentionsParser\Authorship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;

class SaveProfileImageJobTest extends TestCase
{
    public function tearDown()
    {
        if (file_exists(public_path() . '/assets/profile-images/example.org/image')) {
            unlink(public_path() . '/assets/profile-images/example.org/image');
            rmdir(public_path() . '/assets/profile-images/example.org');
        }
        parent::tearDown();
    }
    public function test_authorship_algo_exception_return_null()
    {
        $mf = ['items' => []];
        $authorship = $this->createMock(Authorship::class);
        $authorship->method('findAuthor')
                   ->will($this->throwException(new AuthorshipParserException));
        $job = new SaveProfileImage($mf);

        $this->assertNull($job->handle($authorship));
    }

    public function test_we_dont_process_twitter_images()
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

    public function test_saving_of_remote_image()
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

    public function test_copying_of_local_image()
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
}
