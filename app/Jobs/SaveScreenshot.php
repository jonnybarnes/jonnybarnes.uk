<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Bookmark;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use JsonException;

class SaveScreenshot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Bookmark $bookmark;

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
     * @throws JsonException
     */
    public function handle(): void
    {
        // A normal Guzzle client
        $client = resolve(Client::class);
        // A Guzzle client with a custom Middleware to retry the CloudConvert API requests
        $retryClient = resolve('RetryClient');

        // First request that CloudConvert takes a screenshot of the URL
        $takeScreenshotJobResponse = $client->request('POST', 'https://api.cloudconvert.com/v2/capture-website', [
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.cloudconvert.token'),
            ],
            'json' => [
                'url' => $this->bookmark->url,
                'output_format' => 'png',
                'screen_width' => 1440,
                'screen_height' => 900,
                'wait_until' => 'networkidle0',
                'wait_time' => 100
            ],
        ]);

        $jobId = json_decode($takeScreenshotJobResponse->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR)->data->id;

        // Now wait till the status job is finished
        $screenshotJobStatusResponse = $retryClient->request('GET', 'https://api.cloudconvert.com/v2/tasks/' . $jobId, [
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.cloudconvert.token'),
            ],
            'query' => [
                'include' => 'payload',
            ],
        ]);

        $finishedCaptureId = json_decode($screenshotJobStatusResponse->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR)->data->id;

        // Now we can create a new job to request thst the screenshot is exported to a temporary URL we can download the screenshot from
        $exportImageJob = $client->request('POST', 'https://api.cloudconvert.com/v2/export/url', [
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.cloudconvert.token'),
            ],
            'json' => [
                'input' => $finishedCaptureId,
                'archive_multiple_files' => false,
            ],
        ]);

        $exportImageJobId = json_decode($exportImageJob->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR)->data->id;

        // Again, wait till the status of this export job is finished
        $finalImageUrlResponse = $retryClient->request('GET', 'https://api.cloudconvert.com/v2/tasks/' . $exportImageJobId, [
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.cloudconvert.token'),
            ],
            'query' => [
                'include' => 'payload',
            ],
        ]);

        // Now we can download the screenshot and save it to the storage
        $finalImageUrl = json_decode($finalImageUrlResponse->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR)->data->url;

        $finalImageUrlContent = $client->request('GET', $finalImageUrl);

        Storage::disk('public')->put('/assets/img/bookmarks/' . $jobId . '.png', $finalImageUrlContent->getBody()->getContents());

        $this->bookmark->screenshot = $jobId;
    }
}
