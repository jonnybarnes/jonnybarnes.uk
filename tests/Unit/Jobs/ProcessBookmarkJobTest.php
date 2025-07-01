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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessBookmarkJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function archive_link_is_saved_by_job_and_screenshot_job_is_queued(): void
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

    #[Test]
    public function archive_link_saved_as_null_when_exception_thrown(): void
    {
        Queue::fake();

        $bookmark = Bookmark::factory()->create();
        $service = $this->createMock(BookmarkService::class);
        $service->method('getArchiveLink')
            ->will($this->throwException(new InternetArchiveException));
        $this->app->instance(BookmarkService::class, $service);

        $job = new ProcessBookmark($bookmark);
        $job->handle();

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'archive' => null,
        ]);
    }
}
