<?php

namespace Tests\Feature;

use Queue;
use App\Like;
use Tests\TestCase;
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
        $response = $this->get('/likes');
        $response->assertViewIs('likes.index');
    }

    public function test_like_micropub_request()
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

    public function test_process_like_job()
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
}
