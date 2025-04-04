<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\InternetArchiveException;
use App\Services\BookmarkService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookmarksTest extends TestCase
{
    /*
     * @\test
     *
     * @\group puppeteer
     *
    public function takeScreenshotOfDuckDuckGo()
    {
        $uuid = (new BookmarkService())->saveScreenshot('https://duckduckgo.com');
        $this->assertTrue(file_exists(public_path() . '/assets/img/bookmarks/' . $uuid . '.png'));
    }*/

    #[Test]
    public function archiveLinkMethodCallsArchiveService(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Location' => '/web/1234/example.org']),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
        $url = (new BookmarkService)->getArchiveLink('https://example.org');
        $this->assertEquals('/web/1234/example.org', $url);
    }

    #[Test]
    public function archiveLinkMethodThrowsAnExceptionOnError(): void
    {
        $this->expectException(InternetArchiveException::class);

        $mock = new MockHandler([
            new Response(403),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
        (new BookmarkService)->getArchiveLink('https://example.org');
    }

    #[Test]
    public function archiveLinkMethodThrowsAnExceptionIfNoLocationReturned(): void
    {
        $this->expectException(InternetArchiveException::class);

        $mock = new MockHandler([
            new Response(200),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
        (new BookmarkService)->getArchiveLink('https://example.org');
    }
}
