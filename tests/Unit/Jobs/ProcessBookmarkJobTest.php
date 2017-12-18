<?php

namespace Tests\Unit\Jobs;

use App\Bookmark;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use App\Jobs\ProcessBookmark;
use GuzzleHttp\Psr7\Response;
use App\Services\BookmarkService;
use GuzzleHttp\Handler\MockHandler;
use App\Exceptions\InternetArchiveException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProcessBookmarkJobTest extends TestCase
{
    use DatabaseTransactions;

    public function test_screenshot_and_archive_link_are_saved()
    {
        $bookmark = Bookmark::find(1);
        $uuid = Uuid::uuid4();
        $service = $this->createMock(BookmarkService::class);
        $service->method('saveScreenshot')
                ->willReturn($uuid->toString());
        $service->method('getArchiveLink')
                ->willReturn('https://web.archive.org/web/1234');
        $this->app->instance(BookmarkService::class, $service);

        $job = new ProcessBookmark($bookmark);
        $job->handle();

        $this->assertDatabaseHas('bookmarks', [
            'screenshot' => $uuid->toString(),
            'archive' => 'https://web.archive.org/web/1234',
        ]);
    }

    public function test_exception_casesu_null_value_for_archive_link()
    {
        $bookmark = Bookmark::find(1);
        $uuid = Uuid::uuid4();
        $service = $this->createMock(BookmarkService::class);
        $service->method('saveScreenshot')
                ->willReturn($uuid->toString());
        $service->method('getArchiveLink')
                ->will($this->throwException(new InternetArchiveException));
        $this->app->instance(BookmarkService::class, $service);

        $job = new ProcessBookmark($bookmark);
        $job->handle();

        $this->assertDatabaseHas('bookmarks', [
            'screenshot' => $uuid->toString(),
            'archive' => null,
        ]);
    }
}
