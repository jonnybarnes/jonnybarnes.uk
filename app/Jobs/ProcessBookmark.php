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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bookmark;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Bookmark  $bookmark
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
