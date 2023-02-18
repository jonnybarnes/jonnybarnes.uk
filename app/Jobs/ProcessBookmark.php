<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\InternetArchiveException;
use App\Models\Bookmark;
use App\Services\BookmarkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBookmark implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Bookmark $bookmark
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        SaveScreenshot::dispatch($this->bookmark);

        try {
            $archiveLink = (resolve(BookmarkService::class))->getArchiveLink($this->bookmark->url);
        } catch (InternetArchiveException) {
            $archiveLink = null;
        }
        $this->bookmark->archive = $archiveLink;

        $this->bookmark->save();
    }
}
