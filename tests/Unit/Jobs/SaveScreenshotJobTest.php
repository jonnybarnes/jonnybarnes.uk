<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SaveScreenshot;
use App\Models\Bookmark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaveScreenshotJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function screenshot_is_saved_by_job(): void
    {
        Storage::fake('public');

        Http::fake([
            'api.cloudconvert.com/v2/capture-website' => Http::response([
                'data' => ['id' => '68d52633-e170-465e-b13e-746c97d01ffb', 'status' => 'finished'],
            ], 201),
            'api.cloudconvert.com/v2/tasks/68d52633-e170-465e-b13e-746c97d01ffb*' => Http::response([
                'data' => ['id' => '68d52633-e170-465e-b13e-746c97d01ffb', 'status' => 'finished'],
            ], 200),
            'api.cloudconvert.com/v2/export/url' => Http::response([
                'data' => ['id' => '27f33137-cc03-4468-aba4-1e1aa8c096fb', 'status' => 'finished'],
            ], 201),
            'api.cloudconvert.com/v2/tasks/27f33137-cc03-4468-aba4-1e1aa8c096fb*' => Http::response([
                'data' => [
                    'id' => '27f33137-cc03-4468-aba4-1e1aa8c096fb',
                    'status' => 'finished',
                    'result' => [
                        'files' => [[
                            'url' => 'https://storage.cloudconvert.com/tasks/27f33137-cc03-4468-aba4-1e1aa8c096fb/theverge.com.png',
                        ]],
                    ],
                ],
            ], 200),
            'storage.cloudconvert.com/*' => Http::response(
                file_get_contents(__DIR__.'/../../theverge.com.png'),
                200,
                ['Content-Type' => 'image/png']
            ),
        ]);

        $bookmark = Bookmark::factory()->create();
        $job = new SaveScreenshot($bookmark);
        $job->handle();
        $bookmark->refresh();

        $this->assertEquals('68d52633-e170-465e-b13e-746c97d01ffb', $bookmark->screenshot);
        Storage::disk('public')->assertExists('/assets/img/bookmarks/'.$bookmark->screenshot.'.png');

        // capture-website, 1x poll (finished immediately), export/url, 1x poll (finished immediately), download
        Http::assertSentCount(5);
    }

    #[Test]
    public function screenshot_job_handles_unfinished_tasks(): void
    {
        Storage::fake('public');

        Http::fake([
            'api.cloudconvert.com/v2/capture-website' => Http::response([
                'data' => ['id' => '68d52633-e170-465e-b13e-746c97d01ffb', 'status' => 'waiting'],
            ], 201),
            'api.cloudconvert.com/v2/tasks/68d52633-e170-465e-b13e-746c97d01ffb*' => Http::sequence()
                ->push(['data' => ['id' => '68d52633-e170-465e-b13e-746c97d01ffb', 'status' => 'waiting']], 200)
                ->push(['data' => ['id' => '68d52633-e170-465e-b13e-746c97d01ffb', 'status' => 'finished']], 200),
            'api.cloudconvert.com/v2/export/url' => Http::response([
                'data' => ['id' => '27f33137-cc03-4468-aba4-1e1aa8c096fb', 'status' => 'waiting'],
            ], 201),
            'api.cloudconvert.com/v2/tasks/27f33137-cc03-4468-aba4-1e1aa8c096fb*' => Http::sequence()
                ->push(['data' => ['id' => '27f33137-cc03-4468-aba4-1e1aa8c096fb', 'status' => 'waiting']], 200)
                ->push([
                    'data' => [
                        'id' => '27f33137-cc03-4468-aba4-1e1aa8c096fb',
                        'status' => 'finished',
                        'result' => [
                            'files' => [[
                                'url' => 'https://storage.cloudconvert.com/tasks/27f33137-cc03-4468-aba4-1e1aa8c096fb/theverge.com.png',
                            ]],
                        ],
                    ],
                ], 200),
            'storage.cloudconvert.com/*' => Http::response(
                file_get_contents(__DIR__.'/../../theverge.com.png'),
                200,
                ['Content-Type' => 'image/png']
            ),
        ]);

        $bookmark = Bookmark::factory()->create();
        $job = new SaveScreenshot($bookmark);
        $job->handle();
        $bookmark->refresh();

        $this->assertEquals('68d52633-e170-465e-b13e-746c97d01ffb', $bookmark->screenshot);
        Storage::disk('public')->assertExists('/assets/img/bookmarks/'.$bookmark->screenshot.'.png');

        // capture-website, 2x poll (waiting then finished), export/url, 2x poll (waiting then finished), download
        Http::assertSentCount(7);
        // Also assert every queued response in each sequence was consumed, no more no less
        Http::assertSequencesAreEmpty();
    }
}
