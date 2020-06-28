<?php

namespace Tests\Feature;

use App\Jobs\ProcessMedia;
use App\Models\Media;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\TestToken;

class MicropubMediaTest extends TestCase
{
    use DatabaseTransactions;
    use TestToken;

    /** @test */
    public function emptyResponseForLastUploadWhenNoneFound()
    {
        // Make sure there’s no media
        Media::all()->each(function ($media) {
            $media->delete();
        });

        $response = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertStatus(404);
    }

    /** @test */
    public function clientCanListLastUpload()
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

        $path = parse_url($response->getData()->location, PHP_URL_PATH);
        $filename = substr($path, 7);

        $lastUploadResponse = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $lastUploadResponse->assertJson(['url' => $response->getData()->location]);

        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function optionsRequestReturnsCorsResponse()
    {
        $response = $this->options('/api/media');

        $response->assertStatus(200);
        $response->assertHeader('access-control-allow-origin', '*');
    }

    /** @test */
    public function mediaEndpointRequestWithInvalidTokenReturns400Response()
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
    public function mediaEndpointRequestWithTokenWithNoScopeReturns400Response()
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
    public function mediaEndpointRequestWithInsufficientTokenScopesReturns401Response()
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
    public function mediaEndpointUploadFile()
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

        $path = parse_url($response->getData()->location, PHP_URL_PATH);
        $filename = substr($path, 7);
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function mediaEndpointUploadAudioFile()
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

        $path = parse_url($response->getData()->location, PHP_URL_PATH);
        $filename = substr($path, 7);
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function mediaEndpointUploadVideoFile()
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

        $path = parse_url($response->getData()->location, PHP_URL_PATH);
        $filename = substr($path, 7);
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function mediaEndpointUploadDocumentFile()
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

        $path = parse_url($response->getData()->location, PHP_URL_PATH);
        $filename = substr($path, 7);
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/') . $filename);
    }

    /** @test */
    public function mediaEndpointUploadInvalidFileReturnsError()
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
