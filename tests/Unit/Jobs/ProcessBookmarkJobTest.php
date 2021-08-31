<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Exceptions\InternetArchiveException;
use App\Jobs\ProcessBookmark;
use App\Models\Bookmark;
use App\Services\BookmarkService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class ProcessBookmarkJobTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function screenshotAndArchiveLinkAreSavedByJob(): void
    {
        $bookmark = Bookmark::factory()->create();
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

    /** @test */
    public function archiveLinkSavedAsNullWhenExceptionThrown(): void
    {
        $bookmark = Bookmark::factory()->create();
        $uuid = Uuid::uuid4();
        $service = $this->createMock(BookmarkService::class);
        $service->method('saveScreenshot')
                ->willReturn($uuid->toString());
        $service->method('getArchiveLink')
                ->will($this->throwException(new InternetArchiveException()));
        $this->app->instance(BookmarkService::class, $service);

        $job = new ProcessBookmark($bookmark);
        $job->handle();

        $this->assertDatabaseHas('bookmarks', [
            'screenshot' => $uuid->toString(),
            'archive' => null,
        ]);
    }
}
