<?php

namespace Tests\Feature;

use Mockery;
use Tests\TestCase;
use App\IndieWebUser;
use IndieAuth\Client as IndieClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MicropubClientControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_json_syntax_is_created_correctly()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(201, ['Location' => 'http://example.org/a'], 'Created'),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $guzzleClient = new GuzzleClient(['handler' => $stack]);

        $this->app->instance(GuzzleClient::class, $guzzleClient);

        $response = $this->post(
            '/micropub',
            [
                'content' => 'Hello Fred',
                'in-reply-to' => 'https://fredbloggs.com/note/abc',
                'mp-syndicate-to' => ['https://twitter.com/jonnybarnes', 'https://facebook.com/jonnybarnes'],
            ]
        );

        $expected = '{"type":["h-entry"],"properties":{"content":["Hello Fred"],"in-reply-to":["https:\/\/fredbloggs.com\/note\/abc"],"mp-syndicate-to":["https:\/\/twitter.com\/jonnybarnes","https:\/\/facebook.com\/jonnybarnes"]}}';
        foreach ($container as $transaction) {
            $this->assertEquals($expected, $transaction['request']->getBody()->getContents());
        }
    }
}
