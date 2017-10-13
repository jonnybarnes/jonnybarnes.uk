<?php

namespace App\Jobs;

use App\Bookmark;
use Ramsey\Uuid\Uuid;
use Illuminate\Bus\Queueable;
use Spatie\Image\Manipulations;
use Spatie\Browsershot\Browsershot;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
    public function handle(Browsershot $browsershot)
    {
        $uuid = Uuid::uuid4();
        $browsershot->url($this->bookmark->url)
                    ->windowSize(960, 640)
                    ->save(public_path() . '/assets/img/bookmarks/' . $uuid . '.png');
        $this->bookmark->screenshot = $uuid;
        $this->bookmark->save();
    }
}
