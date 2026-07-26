<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Bookmark;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SaveScreenshot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Bookmark $bookmark
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cloudConvert = Http::baseUrl('https://api.cloudconvert.com/v2')
            ->withToken(config('services.cloudconvert.token'))
            ->throw();

        // First request that CloudConvert takes a screenshot of the URL
        $takeScreenshotJobResponse = $cloudConvert->post('/capture-website', [
            'url' => $this->bookmark->url,
            'output_format' => 'png',
            'screen_width' => 1440,
            'screen_height' => 900,
            'wait_until' => 'networkidle0',
            'wait_time' => 100,
        ]);

        $taskId = $takeScreenshotJobResponse->json('data.id');

        // Now wait till the status job is finished
        $screenshotJobStatusResponse = $this->pollUntilFinished($cloudConvert, $taskId);

        $finishedCaptureId = $screenshotJobStatusResponse->json('data.id');

        // Now we can create a new job to request thst the screenshot is exported to a temporary URL we can download the screenshot from
        $exportImageJob = $cloudConvert->post('/export/url', [
            'input' => $finishedCaptureId,
            'archive_multiple_files' => false,
        ]);

        $exportImageJobId = $exportImageJob->json('data.id');

        // Again, wait till the status of this export job is finished
        $finalImageUrlResponse = $this->pollUntilFinished($cloudConvert, $exportImageJobId);

        // Now we can download the screenshot and save it to the storage
        $finalImageUrl = $finalImageUrlResponse->json('data.result.files.0.url');

        $finalImageUrlContent = Http::throw()->get($finalImageUrl);

        Storage::disk('public')->put('/assets/img/bookmarks/'.$taskId.'.png', $finalImageUrlContent->body());

        $this->bookmark->screenshot = $taskId;
        $this->bookmark->save();
    }

    /**
     * Poll a CloudConvert task until it reports a "finished" status.
     */
    private function pollUntilFinished(PendingRequest $client, string $taskId): Response
    {
        $attempts = 0;

        do {
            $response = $client->get('/tasks/'.$taskId, ['include' => 'payload']);
            $finished = $response->json('data.status') === 'finished';
            if (! $finished) {
                $attempts++;
                usleep(1_000_000); // 1 second, matches CloudConvert's own polling guidance
            }
        } while (! $finished && $attempts < 5);

        return $response;
    }
}
