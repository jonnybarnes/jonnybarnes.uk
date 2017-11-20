<?php

namespace App\Jobs;

use App\Bookmark;
use Illuminate\Bus\Queueable;
use App\Services\BookmarkService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Exceptions\InternetArchiveErrorSavingException;

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
        $uuid = (new BookmarkService())->saveScreenshot($this->bookmark->url);
        $this->bookmark->screenshot = $uuid;

        try {
            $archiveLink = (new BookmarkService())->getArchiveLink($this->bookmark->url);
        } catch (InternetArchiveErrorSavingException $e) {
            $archiveLink = null;
        }
        $this->bookmark->archive = $archiveLink;

        $this->bookmark->save();
    }
}
