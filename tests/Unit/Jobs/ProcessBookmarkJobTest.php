<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Exceptions\InternetArchiveException;
use App\Jobs\ProcessBookmark;
use App\Jobs\SaveScreenshot;
use App\Models\Bookmark;
use App\Services\BookmarkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessBookmarkJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function archiveLinkIsSavedByJobAndScreenshotJobIsQueued(): void
    {
        Queue::fake();

        $bookmark = Bookmark::factory()->create();
        $service = $this->createMock(BookmarkService::class);
        $service->method('getArchiveLink')
                ->willReturn('https://web.archive.org/web/1234');
        $this->app->instance(BookmarkService::class, $service);

        $job = new ProcessBookmark($bookmark);
        $job->handle();

        $this->assertDatabaseHas('bookmarks', [
            'archive' => 'https://web.archive.org/web/1234',
        ]);

        Queue::assertPushed(SaveScreenshot::class);
    }

    /** @test */
    public function archiveLinkSavedAsNullWhenExceptionThrown(): void
    {
        Queue::fake();

        $bookmark = Bookmark::factory()->create();
        $service = $this->createMock(BookmarkService::class);
        $service->method('getArchiveLink')
                ->will($this->throwException(new InternetArchiveException()));
        $this->app->instance(BookmarkService::class, $service);

        $job = new ProcessBookmark($bookmark);
        $job->handle();

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'archive' => null,
        ]);
    }
}
