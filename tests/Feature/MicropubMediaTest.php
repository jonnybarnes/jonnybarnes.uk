<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessMedia;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\TestToken;

class MicropubMediaTest extends TestCase
{
    use RefreshDatabase;
    use TestToken;

    /** @test */
    public function emptyResponseForLastUploadWhenNoneFound(): void
    {
        // Make sure there’s no media
        Media::all()->each(function ($media) {
            $media->delete();
        });

        $response = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertStatus(200);
        $response->assertJson(['url' => null]);
    }

    /** @test */
    public function getRequestWithInvalidTokenReturnsErrorResponse(): void
    {
        $response = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer abc123']
        );
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token did not pass validation']);
    }

    /** @test */
    public function getRequestWithTokenWithoutScopeReturnsErrorResponse(): void
    {
        $response = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithNoScope()]
        );
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token has no scopes']);
    }

    /** @test */
    public function getRequestWithTokenWithInsufficientScopeReturnsErrorResponse(): void
    {
        $response = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithIncorrectScope()]
        );
        $response->assertStatus(401);
        $response->assertJsonFragment(['error_description' => 'The token’s scope does not have the necessary requirements.']);
    }

    /** @test */
    public function emptyGetRequestWithTokenReceivesOkResponse(): void
    {
        $response = $this->get(
            '/api/media',
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertStatus(200);
        $response->assertJson(['status' => 'OK']);
    }

    /** @test */
    public function clientCanListLastUpload(): void
    {
        Queue::fake();
        Storage::fake('s3');
        $file = __DIR__ . '/../aaron.png';
        $token = $this->getToken();
        config(['filesystems.disks.s3.url' => 'https://s3.example.com']);

        $response = $this->post(
            '/api/media',
            [
                'file' => new UploadedFile($file, 'aaron.png', 'image/png', null, true),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $token]
        );

        $location = $response->headers->get('Location');

        $this->assertStringStartsWith('https://s3.example.com/', $location);

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = substr($path, 7);

        $lastUploadResponse = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer ' . $token]
        );
        $lastUploadResponse->assertJson(['url' => $response->headers->get('Location')]);

        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function clientCanSourceUploads(): void
    {
        Queue::fake();
        Storage::fake('s3');
        $file = __DIR__ . '/../aaron.png';
        $token = $this->getToken();

        $response = $this->post(
            '/api/media',
            [
                'file' => new UploadedFile($file, 'aaron.png', 'image/png', null, true),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $token]
        );

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = substr($path, 7);

        $sourceUploadResponse = $this->get(
            '/api/media?q=source',
            ['HTTP_Authorization' => 'Bearer ' . $token]
        );
        $sourceUploadResponse->assertJson(['items' => [[
            'url' => $response->headers->get('Location'),
            'mime_type' => 'image/png',
        ]]]);

        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function clientCanSourceUploadsWithLimit(): void
    {
        Queue::fake();
        Storage::fake('s3');
        $file = __DIR__ . '/../aaron.png';
        $token = $this->getToken();

        $response = $this->post(
            '/api/media',
            [
                'file' => new UploadedFile($file, 'aaron.png', 'image/png', null, true),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $token]
        );

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = substr($path, 7);

        $sourceUploadResponse = $this->get(
            '/api/media?q=source&limit=1',
            ['HTTP_Authorization' => 'Bearer ' . $token]
        );
        $sourceUploadResponse->assertJson(['items' => [[
            'url' => $response->headers->get('Location'),
            'mime_type' => 'image/png',
        ]]]);
        // And given our limit of 1 there should only be one result
        $this->assertCount(1, json_decode($sourceUploadResponse->getContent(), true)['items']);

        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function mediaEndpointUploadRequiresFile(): void
    {
        $response = $this->post(
            '/api/media',
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertStatus(400);
        $response->assertJson([
            'response' => 'error',
            'error' => 'invalid_request',
            'error_description' => 'No file was sent with the request',
        ]);
    }

    /** @test */
    public function errorResponseForUnknownQValue(): void
    {
        $response = $this->get(
            '/api/media?q=unknown',
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertStatus(400);
        $response->assertJson(['error' => 'invalid_request']);
    }

    /** @test */
    public function optionsRequestReturnsCorsResponse(): void
    {
        $response = $this->options('/api/media');

        $response->assertStatus(200);
        $response->assertHeader('access-control-allow-origin', '*');
    }

    /** @test */
    public function mediaEndpointRequestWithInvalidTokenReturns400Response(): void
    {
        $response = $this->post(
            '/api/media',
            [],
            ['HTTP_Authorization' => 'Bearer abc123']
        );
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token did not pass validation']);
    }

    /** @test */
    public function mediaEndpointRequestWithTokenWithNoScopeReturns400Response(): void
    {
        $response = $this->post(
            '/api/media',
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithNoScope()]
        );
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token has no scopes']);
    }

    /** @test */
    public function mediaEndpointRequestWithInsufficientTokenScopesReturns401Response(): void
    {
        $response = $this->post(
            '/api/media',
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithIncorrectScope()]
        );
        $response->assertStatus(401);
        $response->assertJsonFragment([
            'error_description' => 'The token’s scope does not have the necessary requirements.',
        ]);
    }

    /** @test */
    public function mediaEndpointUploadFile(): void
    {
        Queue::fake();
        Storage::fake('s3');
        $file = __DIR__ . '/../aaron.png';

        $response = $this->post(
            '/api/media',
            [
                'file' => new UploadedFile($file, 'aaron.png', 'image/png', null, true),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = substr($path, 7);
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function mediaEndpointUploadAudioFile(): void
    {
        Queue::fake();
        Storage::fake('s3');
        $file = __DIR__ . '/../audio.mp3';

        $response = $this->post(
            '/api/media',
            [
                'file' => new UploadedFile($file, 'audio.mp3', 'audio/mpeg', null, true),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = substr($path, 7);
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function mediaEndpointUploadVideoFile(): void
    {
        Queue::fake();
        Storage::fake('s3');
        $file = __DIR__ . '/../video.ogv';

        $response = $this->post(
            '/api/media',
            [
                'file' => new UploadedFile($file, 'video.ogv', 'video/ogg', null, true),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = substr($path, 7);
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function mediaEndpointUploadDocumentFile(): void
    {
        Queue::fake();
        Storage::fake('s3');

        $response = $this->post(
            '/api/media',
            [
                'file' => UploadedFile::fake()->create('document.pdf', 100),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = substr($path, 7);
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function mediaEndpointUploadInvalidFileReturnsError(): void
    {
        Queue::fake();
        Storage::fake('local');

        $response = $this->post(
            '/api/media',
            [
                'file' => new UploadedFile(
                    __DIR__ . '/../aaron.png',
                    'aaron.png',
                    'image/png',
                    UPLOAD_ERR_INI_SIZE,
                    true
                ),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertStatus(400);
        $response->assertJson(['error_description' => 'The uploaded file failed validation']);
    }
}
