<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\InternetArchiveException;
use App\Services\BookmarkService;
use Illuminate\Support\Facades\Http;
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
    public function archive_link_method_calls_archive_service(): void
    {
        Http::fake([
            'web.archive.org/*' => Http::response('', 200, ['Content-Location' => '/web/1234/example.org']),
        ]);
        $url = (new BookmarkService)->getArchiveLink('https://example.org');
        $this->assertEquals('/web/1234/example.org', $url);
    }

    #[Test]
    public function archive_link_method_throws_an_exception_on_error(): void
    {
        $this->expectException(InternetArchiveException::class);

        Http::fake([
            'web.archive.org/*' => Http::response('', 403),
        ]);
        (new BookmarkService)->getArchiveLink('https://example.org');
    }

    #[Test]
    public function archive_link_method_throws_an_exception_if_no_location_returned(): void
    {
        $this->expectException(InternetArchiveException::class);

        Http::fake([
            'web.archive.org/*' => Http::response('', 200),
        ]);
        (new BookmarkService)->getArchiveLink('https://example.org');
    }
}
