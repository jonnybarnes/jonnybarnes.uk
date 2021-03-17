<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\DownloadWebMention;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReDownloadWebMentionsTest extends TestCase
{
    /** @test */
    public function downloadJobGetsQueued(): void
    {
        Queue::fake();

        Artisan::call('webmentions:redownload');

        Queue::assertPushed(DownloadWebMention::class);
    }
}
