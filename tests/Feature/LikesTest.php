<?php

namespace Tests\Feature;

use Queue;
use Tests\TestCase;
use App\Models\Like;
use Tests\TestToken;
use GuzzleHttp\Client;
use App\Jobs\ProcessLike;
use Lcobucci\JWT\Builder;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Jonnybarnes\WebmentionsParser\Authorship;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LikesTest extends TestCase
{
    use DatabaseTransactions, TestToken;

    public function test_likes_page()
    {
        $response = $this->get('/likes');
        $response->assertViewIs('likes.index');
    }

    public function test_single_like_page()
    {
        $response = $this->get('/likes/1');
        $response->assertViewIs('likes.show');
    }

    public function test_like_micropub_json_request()
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

    public function test_like_micropub_form_request()
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

    public function test_process_like_job_with_simple_author()
    {
        $like = new Like();
        $like->url = 'http://example.org/note/id';
        $like->save();
        $id = $like->id;

        $job = new ProcessLike($like);

        $content = <<<END
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
        $this->app->bind(Client::class, $client);
        $authorship = new Authorship();

        $job->handle($client, $authorship);

        $this->assertEquals('Fred Bloggs', Like::find($id)->author_name);
    }

    public function test_process_like_job_with_h_card()
    {
        $like = new Like();
        $like->url = 'http://example.org/note/id';
        $like->save();
        $id = $like->id;

        $job = new ProcessLike($like);

        $content = <<<END
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
        $this->app->bind(Client::class, $client);
        $authorship = new Authorship();

        $job->handle($client, $authorship);

        $this->assertEquals('Fred Bloggs', Like::find($id)->author_name);
    }

    public function test_process_like_job_without_mf2()
    {
        $like = new Like();
        $like->url = 'http://example.org/note/id';
        $like->save();
        $id = $like->id;

        $job = new ProcessLike($like);

        $content = <<<END
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
        $this->app->bind(Client::class, $client);
        $authorship = new Authorship();

        $job->handle($client, $authorship);

        $this->assertNull(Like::find($id)->author_name);
    }

    public function test_process_like_that_is_tweet()
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
        $this->app->bind(Client::class, $client);
        $authorship = new Authorship();

        $job->handle($client, $authorship);

        $this->assertEquals('Jonny Barnes', Like::find($id)->author_name);
    }

    public function test_process_like_that_is_tweet_with_oembed_error()
    {
        $like = new Like();
        $like->url = 'https://twitter.com/jonnybarnes/status/1050823255123251200';
        $like->save();
        $id = $like->id;

        $job = new ProcessLike($like);

        $mock = new MockHandler([
            new Response(500),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->bind(Client::class, $client);
        $authorship = new Authorship();

        $job->handle($client, $authorship);

        $this->assertEquals('Jonny Barnes', Like::find($id)->author_name);
    }
}
