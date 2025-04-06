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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\TestToken;

class LikesTest extends TestCase
{
    use RefreshDatabase;
    use TestToken;

    #[Test]
    public function likes_page_has_correct_view(): void
    {
        $response = $this->get('/likes');
        $response->assertViewIs('likes.index');
    }

    #[Test]
    public function single_like_page_has_correct_view(): void
    {
        $like = Like::factory()->create();
        $response = $this->get('/likes/' . $like->id);
        $response->assertViewIs('likes.show');
    }

    #[Test]
    public function check_like_created_from_micropub_api_requests(): void
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

    #[Test]
    public function check_like_created_from_micropub_web_requests(): void
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

    #[Test]
    public function like_with_simple_author(): void
    {
        $like = new Like;
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
        $authorship = new Authorship;

        $job->handle($client, $authorship);

        $this->assertEquals('Fred Bloggs', Like::find($id)->author_name);
    }

    #[Test]
    public function like_with_h_card(): void
    {
        $like = new Like;
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
        $authorship = new Authorship;

        $job->handle($client, $authorship);

        $this->assertEquals('Fred Bloggs', Like::find($id)->author_name);
    }

    #[Test]
    public function like_without_microformats(): void
    {
        $like = new Like;
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
        $authorship = new Authorship;

        $job->handle($client, $authorship);

        $this->assertNull(Like::find($id)->author_name);
    }

    #[Test]
    public function like_that_is_a_tweet(): void
    {
        $like = new Like;
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
        $codebirdMock = $this->createPartialMock(Codebird::class, ['__call']);
        $codebirdMock->method('__call')
            ->with('statuses_oembed', $this->anything())
            ->willReturn($info);
        $this->app->instance(Codebird::class, $codebirdMock);

        $authorship = new Authorship;

        $job->handle($client, $authorship);

        $this->assertEquals('Jonny Barnes', Like::find($id)->author_name);
    }

    #[Test]
    public function no_error_for_failure_to_posse_with_bridgy(): void
    {
        $like = new Like;
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
        $codebirdMock = $this->createPartialMock(Codebird::class, ['__call']);
        $codebirdMock->method('__call')
            ->with('statuses_oembed', $this->anything())
            ->willReturn($info);
        $this->app->instance(Codebird::class, $codebirdMock);

        $authorship = new Authorship;

        $job->handle($client, $authorship);

        $this->assertEquals('Jonny Barnes', Like::find($id)->author_name);
    }

    #[Test]
    public function unknown_like_gives_not_found_response(): void
    {
        $response = $this->get('/likes/202');
        $response->assertNotFound();
    }
}
