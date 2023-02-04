<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SaveScreenshot;
use App\Models\Bookmark;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaveScreenshotJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function screenshotIsSavedByJob(): void
    {
        Storage::fake('public');
        $guzzleMock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], '{"data":{"id":"68d52633-e170-465e-b13e-746c97d01ffb","job_id":null,"status":"finished","credits":null,"code":null,"message":null,"percent":100,"operation":"capture-website","engine":"chrome","engine_version":"107","result":null,"created_at":"2023-01-07T21:05:48+00:00","started_at":null,"ended_at":null,"retry_of_task_id":null,"copy_of_task_id":null,"user_id":61485254,"priority":-10,"host_name":null,"storage":"ceph-fra","depends_on_task_ids":[],"links":{"self":"https:\/\/api.cloudconvert.com\/v2\/tasks\/68d52633-e170-465e-b13e-746c97d01ffb"}}}'),
            new Response(201, ['Content-Type' => 'application/json'], '{"data":{"id":"27f33137-cc03-4468-aba4-1e1aa8c096fb","job_id":null,"status":"finished","credits":null,"code":null,"message":null,"percent":100,"operation":"export\/url","result":null,"created_at":"2023-01-07T21:10:02+00:00","started_at":null,"ended_at":null,"retry_of_task_id":null,"copy_of_task_id":null,"user_id":61485254,"priority":-10,"host_name":null,"storage":"ceph-fra","depends_on_task_ids":["68d52633-e170-465e-b13e-746c97d01ffb"],"links":{"self":"https:\/\/api.cloudconvert.com\/v2\/tasks\/27f33137-cc03-4468-aba4-1e1aa8c096fb"}}}'),
            new Response(200, ['Content-Type' => 'image/png'], fopen(__DIR__ . '/../../theverge.com.png', 'rb')),
        ]);
        $guzzleHandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHandler]);
        $this->app->instance(Client::class, $guzzleClient);
        $retryMock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"data":{"id":"68d52633-e170-465e-b13e-746c97d01ffb","job_id":null,"status":"finished","credits":1,"code":null,"message":null,"percent":100,"operation":"capture-website","engine":"chrome","engine_version":"107","payload":{"url":"https:\/\/theverge.com","output_format":"png","screen_width":1440,"screen_height":900,"wait_until":"networkidle0","wait_time":"100"},"result":{"files":[{"filename":"theverge.com.png","size":811819}]},"created_at":"2023-01-07T21:05:48+00:00","started_at":"2023-01-07T21:05:48+00:00","ended_at":"2023-01-07T21:05:55+00:00","retry_of_task_id":null,"copy_of_task_id":null,"user_id":61485254,"priority":-10,"host_name":"virgie","storage":"ceph-fra","depends_on_task_ids":[],"links":{"self":"https:\/\/api.cloudconvert.com\/v2\/tasks\/68d52633-e170-465e-b13e-746c97d01ffb"}}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"data":{"id":"27f33137-cc03-4468-aba4-1e1aa8c096fb","job_id":null,"status":"finished","credits":0,"code":null,"message":null,"percent":100,"operation":"export\/url","payload":{"input":"68d52633-e170-465e-b13e-746c97d01ffb","archive_multiple_files":false},"result":{"files":[{"filename":"theverge.com.png","size":811819,"url":"https:\/\/storage.cloudconvert.com\/tasks\/27f33137-cc03-4468-aba4-1e1aa8c096fb\/theverge.com.png?AWSAccessKeyId=cloudconvert-production&Expires=1673212203&Signature=xyz&response-content-disposition=attachment%3B%20filename%3D%22theverge.com.png%22&response-content-type=image%2Fpng"}]},"created_at":"2023-01-07T21:10:02+00:00","started_at":"2023-01-07T21:10:03+00:00","ended_at":"2023-01-07T21:10:03+00:00","retry_of_task_id":null,"copy_of_task_id":null,"user_id":61485254,"priority":-10,"host_name":"virgie","storage":"ceph-fra","depends_on_task_ids":["68d52633-e170-465e-b13e-746c97d01ffb"],"links":{"self":"https:\/\/api.cloudconvert.com\/v2\/tasks\/27f33137-cc03-4468-aba4-1e1aa8c096fb"}}}'),
        ]);
        $retryHandler = HandlerStack::create($retryMock);
        $retryHandler->push(Middleware::retry(
            function ($retries, $request, $response, $exception) {
                // Limit the number of retries to 5
                if ($retries >= 5) {
                    return false;
                }

                // Retry connection exceptions
                if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                    return true;
                }

                // Retry on server errors
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                $responseBody = '';

                if (is_string($response)) {
                    $responseBody = $response;
                }

                if ($response instanceof Response) {
                    $responseBody = $response->getBody()->getContents();
                    $response->getBody()->rewind();
                }

                // Finally for CloudConvert, retry if status is not final
                return json_decode($responseBody, false, 512, JSON_THROW_ON_ERROR)?->data?->status !== 'finished';
            },
            function () {
                // Retry after 1 second
                return 1000;
            }
        ));
        $retryClient = new Client(['handler' => $retryHandler]);
        $this->app->instance('RetryGuzzle', $retryClient);

        $bookmark = Bookmark::factory()->create();
        $job = new SaveScreenshot($bookmark);
        $job->handle();
        $bookmark->refresh();

        $this->assertEquals('68d52633-e170-465e-b13e-746c97d01ffb', $bookmark->screenshot);
        Storage::disk('public')->assertExists('/assets/img/bookmarks/' . $bookmark->screenshot . '.png');
    }

    /** @test */
    public function screenshotJobHandlesUnfinishedTasks(): void
    {
        Storage::fake('public');
        $guzzleMock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], '{"id":1,"data":{"id":"68d52633-e170-465e-b13e-746c97d01ffb","job_id":null,"status":"waiting","credits":null,"code":null,"message":null,"percent":100,"operation":"capture-website","engine":"chrome","engine_version":"107","result":null,"created_at":"2023-01-07T21:05:48+00:00","started_at":null,"ended_at":null,"retry_of_task_id":null,"copy_of_task_id":null,"user_id":61485254,"priority":-10,"host_name":null,"storage":"ceph-fra","depends_on_task_ids":[],"links":{"self":"https:\/\/api.cloudconvert.com\/v2\/tasks\/68d52633-e170-465e-b13e-746c97d01ffb"}}}'),
            new Response(201, ['Content-Type' => 'application/json'], '{"id":2,"data":{"id":"27f33137-cc03-4468-aba4-1e1aa8c096fb","job_id":null,"status":"waiting","credits":null,"code":null,"message":null,"percent":100,"operation":"export\/url","result":null,"created_at":"2023-01-07T21:10:02+00:00","started_at":null,"ended_at":null,"retry_of_task_id":null,"copy_of_task_id":null,"user_id":61485254,"priority":-10,"host_name":null,"storage":"ceph-fra","depends_on_task_ids":["68d52633-e170-465e-b13e-746c97d01ffb"],"links":{"self":"https:\/\/api.cloudconvert.com\/v2\/tasks\/27f33137-cc03-4468-aba4-1e1aa8c096fb"}}}'),
            new Response(200, ['Content-Type' => 'image/png'], fopen(__DIR__ . '/../../theverge.com.png', 'rb')),
        ]);
        $guzzleHandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHandler]);
        $this->app->instance(Client::class, $guzzleClient);
        $container = [];
        $history = Middleware::history($container);
        $retryMock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":3,"data":{"id":"68d52633-e170-465e-b13e-746c97d01ffb","job_id":null,"status":"waiting","credits":1,"code":null,"message":null,"percent":50,"operation":"capture-website","engine":"chrome","engine_version":"107","payload":{"url":"https:\/\/theverge.com","output_format":"png","screen_width":1440,"screen_height":900,"wait_until":"networkidle0","wait_time":"100"},"result":{"files":[{"filename":"theverge.com.png","size":811819}]},"created_at":"2023-01-07T21:05:48+00:00","started_at":"2023-01-07T21:05:48+00:00","ended_at":"2023-01-07T21:05:55+00:00","retry_of_task_id":null,"copy_of_task_id":null,"user_id":61485254,"priority":-10,"host_name":"virgie","storage":"ceph-fra","depends_on_task_ids":[],"links":{"self":"https:\/\/api.cloudconvert.com\/v2\/tasks\/68d52633-e170-465e-b13e-746c97d01ffb"}}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":4,"data":{"id":"68d52633-e170-465e-b13e-746c97d01ffb","job_id":null,"status":"finished","credits":1,"code":null,"message":null,"percent":100,"operation":"capture-website","engine":"chrome","engine_version":"107","payload":{"url":"https:\/\/theverge.com","output_format":"png","screen_width":1440,"screen_height":900,"wait_until":"networkidle0","wait_time":"100"},"result":{"files":[{"filename":"theverge.com.png","size":811819}]},"created_at":"2023-01-07T21:05:48+00:00","started_at":"2023-01-07T21:05:48+00:00","ended_at":"2023-01-07T21:05:55+00:00","retry_of_task_id":null,"copy_of_task_id":null,"user_id":61485254,"priority":-10,"host_name":"virgie","storage":"ceph-fra","depends_on_task_ids":[],"links":{"self":"https:\/\/api.cloudconvert.com\/v2\/tasks\/68d52633-e170-465e-b13e-746c97d01ffb"}}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":5,"data":{"id":"27f33137-cc03-4468-aba4-1e1aa8c096fb","job_id":null,"status":"waiting","credits":0,"code":null,"message":null,"percent":50,"operation":"export\/url","payload":{"input":"68d52633-e170-465e-b13e-746c97d01ffb","archive_multiple_files":false},"created_at":"2023-01-07T21:10:02+00:00","started_at":"2023-01-07T21:10:03+00:00","ended_at":null,"retry_of_task_id":null,"copy_of_task_id":null,"user_id":61485254,"priority":-10,"host_name":"virgie","storage":"ceph-fra","depends_on_task_ids":["68d52633-e170-465e-b13e-746c97d01ffb"],"links":{"self":"https:\/\/api.cloudconvert.com\/v2\/tasks\/27f33137-cc03-4468-aba4-1e1aa8c096fb"}}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":6,"data":{"id":"27f33137-cc03-4468-aba4-1e1aa8c096fb","job_id":null,"status":"finished","credits":0,"code":null,"message":null,"percent":100,"operation":"export\/url","payload":{"input":"68d52633-e170-465e-b13e-746c97d01ffb","archive_multiple_files":false},"result":{"files":[{"filename":"theverge.com.png","size":811819,"url":"https:\/\/storage.cloudconvert.com\/tasks\/27f33137-cc03-4468-aba4-1e1aa8c096fb\/theverge.com.png?AWSAccessKeyId=cloudconvert-production&Expires=1673212203&Signature=xyz&response-content-disposition=attachment%3B%20filename%3D%22theverge.com.png%22&response-content-type=image%2Fpng"}]},"created_at":"2023-01-07T21:10:02+00:00","started_at":"2023-01-07T21:10:03+00:00","ended_at":"2023-01-07T21:10:03+00:00","retry_of_task_id":null,"copy_of_task_id":null,"user_id":61485254,"priority":-10,"host_name":"virgie","storage":"ceph-fra","depends_on_task_ids":["68d52633-e170-465e-b13e-746c97d01ffb"],"links":{"self":"https:\/\/api.cloudconvert.com\/v2\/tasks\/27f33137-cc03-4468-aba4-1e1aa8c096fb"}}}'),
        ]);
        $retryHandler = HandlerStack::create($retryMock);
        $retryHandler->push($history);
        $retryHandler->push(Middleware::retry(
            function ($retries, $request, $response, $exception) {
                // Limit the number of retries to 5
                if ($retries >= 5) {
                    return false;
                }

                // Retry connection exceptions
                if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                    return true;
                }

                // Retry on server errors
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                $responseBody = '';

                if (is_string($response)) {
                    $responseBody = $response;
                }

                if ($response instanceof Response) {
                    $responseBody = $response->getBody()->getContents();
                    $response->getBody()->rewind();
                }

                // Finally for CloudConvert, retry if status is not final
                return json_decode($responseBody, false, 512, JSON_THROW_ON_ERROR)?->data?->status !== 'finished';
            },
            function () {
                // Retry after 1 second
                return 1000;
            }
        ));
        $retryClient = new Client(['handler' => $retryHandler]);
        $this->app->instance('RetryGuzzle', $retryClient);

        $bookmark = Bookmark::factory()->create();
        $job = new SaveScreenshot($bookmark);
        $job->handle();
        $bookmark->refresh();

        $this->assertEquals('68d52633-e170-465e-b13e-746c97d01ffb', $bookmark->screenshot);
        Storage::disk('public')->assertExists('/assets/img/bookmarks/' . $bookmark->screenshot . '.png');
        // Also assert we made the correct number of requests
        $this->assertCount(2, $container);
        // However with retries there should be more than 4 responses for the 2 requests
        $this->assertEquals(0, $retryMock->count());
    }
}
