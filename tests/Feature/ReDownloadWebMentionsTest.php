<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\DownloadWebMention;
use App\Models\WebMention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReDownloadWebMentionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function downloadJobGetsQueued(): void
    {
        Queue::fake();

        WebMention::factory()->create();

        Artisan::call('webmentions:redownload');

        Queue::assertPushed(DownloadWebMention::class);
    }
}
