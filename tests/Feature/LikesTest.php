<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessLike;
use App\Models\Like;
use Codebird\Codebird;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Jonnybarnes\WebmentionsParser\Authorship;
use Tests\TestCase;
use Tests\TestToken;

class LikesTest extends TestCase
{
    use RefreshDatabase;
    use TestToken;

    /** @test */
    public function likesPageHasCorrectView(): void
    {
        $response = $this->get('/likes');
        $response->assertViewIs('likes.index');
    }

    /** @test */
    public function singleLikePageHasCorrectView(): void
    {
        $like = Like::factory()->create();
        $response = $this->get('/likes/' . $like->id);
        $response->assertViewIs('likes.show');
    }

    /** @test */
    public function checkLikeCreatedFromMicropubApiRequests(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
        ])->json('POST', '/api/post', [
            'type' => ['h-entry'],
            'properties' => [
                'like-of' => ['https://example.org/blog-post'],
            ],
        ]);

        $response->assertJson(['response' => 'created']);

        Queue::assertPushed(ProcessLike::class);
        $this->assertDatabaseHas('likes', ['url' => 'https://example.org/blog-post']);
    }

    /** @test */
    public function checkLikeCreatedFromMicropubWebRequests(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
        ])->post('/api/post', [
            'h' => 'entry',
            'like-of' => 'https://example.org/blog-post',
        ]);

        $response->assertStatus(201);

        Queue::assertPushed(ProcessLike::class);
        $this->assertDatabaseHas('likes', ['url' => 'https://example.org/blog-post']);
    }

    /** @test */
    public function likeWithSimpleAuthor(): void
    {
        $like = new Like();
        $like->url = 'http://example.org/note/id';
        $like->save();
        $id = $like->id;

        $job = new ProcessLike($like);

        $content = <<<'END'
        <html>
            <body>
                <div class="h-entry">
                    <div class="e-content">
                        A post that I like.
                    </div>
                    by <span class="p-author">Fred Bloggs</span>
                </div>
            </body>
        </html>
        END;

        $mock = new MockHandler([
            new Response(200, [], $content),
            new Response(200, [], $content),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->bind(Client::class, function () use ($client) {
            return $client;
        });
        $authorship = new Authorship();

        $job->handle($client, $authorship);

        $this->assertEquals('Fred Bloggs', Like::find($id)->author_name);
    }

    /** @test */
    public function likeWithHCard(): void
    {
        $like = new Like();
        $like->url = 'http://example.org/note/id';
        $like->save();
        $id = $like->id;

        $job = new ProcessLike($like);

        $content = <<<'END'
        <html>
            <body>
                <div class="h-entry">
                    <div class="e-content">
                        A post that I like.
                    </div>
                    by
                    <div class="p-author h-card">
                        <span class="p-name">Fred Bloggs</span>
                        <a class="u-url" href="https://fredd.blog/gs"></a>
                    </div>
                </div>
            </body>
        </html>
        END;

        $mock = new MockHandler([
            new Response(200, [], $content),
            new Response(200, [], $content),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->bind(Client::class, function () use ($client) {
            return $client;
        });
        $authorship = new Authorship();

        $job->handle($client, $authorship);

        $this->assertEquals('Fred Bloggs', Like::find($id)->author_name);
    }

    /** @test */
    public function likeWithoutMicroformats(): void
    {
        $like = new Like();
        $like->url = 'http://example.org/note/id';
        $like->save();
        $id = $like->id;

        $job = new ProcessLike($like);

        $content = <<<'END'
        <html>
            <body>
                <div>
                    I liked a post
                </div>
            </body>
        </html>
        END;

        $mock = new MockHandler([
            new Response(200, [], $content),
            new Response(200, [], $content),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->bind(Client::class, function () use ($client) {
            return $client;
        });
        $authorship = new Authorship();

        $job->handle($client, $authorship);

        $this->assertNull(Like::find($id)->author_name);
    }

    /** @test */
    public function likeThatIsATweet(): void
    {
        $like = new Like();
        $like->url = 'https://twitter.com/jonnybarnes/status/1050823255123251200';
        $like->save();
        $id = $like->id;

        $job = new ProcessLike($like);

        $mock = new MockHandler([
            new Response(201, [], json_encode([
                'url' => 'https://twitter.com/likes/id',
            ])),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->bind(Client::class, function () use ($client) {
            return $client;
        });

        $info = (object) [
            'author_name' => 'Jonny Barnes',
            'author_url' => 'https://twitter.com/jonnybarnes',
            'html' => '<div>HTML of the tweet embed</div>',
        ];
        $codebirdMock = $this->getMockBuilder(Codebird::class)
            ->addMethods(['statuses_oembed'])
            ->getMock();
        $codebirdMock->method('statuses_oembed')
            ->willReturn($info);
        $this->app->instance(Codebird::class, $codebirdMock);

        $authorship = new Authorship();

        $job->handle($client, $authorship);

        $this->assertEquals('Jonny Barnes', Like::find($id)->author_name);
    }

    /** @test */
    public function noErrorForFailureToPosseWithBridgy(): void
    {
        $like = new Like();
        $like->url = 'https://twitter.com/jonnybarnes/status/1050823255123251200';
        $like->save();
        $id = $like->id;

        $job = new ProcessLike($like);

        $mock = new MockHandler([
            new Response(404, [], 'Not found'),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->bind(Client::class, function () use ($client) {
            return $client;
        });

        $info = (object) [
            'author_name' => 'Jonny Barnes',
            'author_url' => 'https://twitter.com/jonnybarnes',
            'html' => '<div>HTML of the tweet embed</div>',
        ];
        $codebirdMock = $this->getMockBuilder(Codebird::class)
            ->addMethods(['statuses_oembed'])
            ->getMock();
        $codebirdMock->method('statuses_oembed')
            ->willReturn($info);
        $this->app->instance(Codebird::class, $codebirdMock);

        $authorship = new Authorship();

        $job->handle($client, $authorship);

        $this->assertEquals('Jonny Barnes', Like::find($id)->author_name);
    }

    /** @test */
    public function unknownLikeGivesNotFoundResponse(): void
    {
        $response = $this->get('/likes/202');
        $response->assertNotFound();
    }
}
