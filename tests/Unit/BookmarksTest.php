<?php

namespace Tests\Unit;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use App\Services\BookmarkService;
use GuzzleHttp\Handler\MockHandler;

class BookmarksTest extends TestCase
{
    public function test_screenshot_of_google()
    {
        $uuid = (new BookmarkService())->saveScreenshot('https://www.google.co.uk');
        $this->assertTrue(file_exists(public_path() . '/assets/img/bookmarks/' . $uuid . '.png'));
    }

    public function test_archive_link_method()
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Location' => '/web/1234/example.org']),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
        $url = (new BookmarkService())->getArchiveLink('https://example.org');
        $this->assertEquals('/web/1234/example.org', $url);
    }

    /**
     * @expectedException App\Exceptions\InternetArchiveException
     */
    public function test_archive_link_method_archive_site_error_exception()
    {
        $mock = new MockHandler([
            new Response(403),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
        $url = (new BookmarkService())->getArchiveLink('https://example.org');
    }

    /**
     * @expectedException App\Exceptions\InternetArchiveException
     */
    public function test_archive_link_method_archive_site_no_location_exception()
    {
        $mock = new MockHandler([
            new Response(200),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
        $url = (new BookmarkService())->getArchiveLink('https://example.org');
    }
}
