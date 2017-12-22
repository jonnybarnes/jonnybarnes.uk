<?php

namespace App\Jobs;

use App\Models\Bookmark;
use Illuminate\Bus\Queueable;
use App\Services\BookmarkService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Exceptions\InternetArchiveException;

class ProcessBookmark implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bookmark;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Bookmark $bookmark)
    {
        $this->bookmark = $bookmark;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $uuid = (resolve(BookmarkService::class))->saveScreenshot($this->bookmark->url);
        $this->bookmark->screenshot = $uuid;

        try {
            $archiveLink = (resolve(BookmarkService::class))->getArchiveLink($this->bookmark->url);
        } catch (InternetArchiveException $e) {
            $archiveLink = null;
        }
        $this->bookmark->archive = $archiveLink;

        $this->bookmark->save();
    }
}
