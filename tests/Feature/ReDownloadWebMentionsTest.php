<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\DownloadWebMention;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;

class ReDownloadWebMentionsTest extends TestCase
{
    public function test_jobs_are_dispatched()
    {
        Queue::fake();

        Artisan::call('webmentions:redownload');

        Queue::assertPushed(DownloadWebMention::class);
    }
}
