<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessMedia;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\TestToken;

class MicropubMediaTest extends TestCase
{
    use RefreshDatabase;
    use TestToken;

    #[Test]
    public function empty_response_for_last_upload_when_none_found(): void
    {
        // Make sure there’s no media
        $this->assertCount(0, Media::all());

        $response = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertStatus(200);
        $response->assertJson(['url' => null]);
    }

    #[Test]
    public function get_request_with_invalid_token_returns_error_response(): void
    {
        $response = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer abc123']
        );
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token did not pass validation']);
    }

    #[Test]
    public function get_request_with_token_without_scope_returns_error_response(): void
    {
        $response = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithNoScope()]
        );
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token has no scopes']);
    }

    #[Test]
    public function get_request_with_token_with_insufficient_scope_returns_error_response(): void
    {
        $response = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithIncorrectScope()]
        );
        $response->assertStatus(401);
        $response->assertJsonFragment(['error_description' => 'The token’s scope does not have the necessary requirements.']);
    }

    #[Test]
    public function empty_get_request_with_token_receives_ok_response(): void
    {
        $response = $this->get(
            '/api/media',
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertStatus(200);
        $response->assertJson(['status' => 'OK']);
    }

    #[Test]
    public function client_can_list_last_upload(): void
    {
        Queue::fake();
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
        $filename = Str::chopStart($path, '/storage/media/');

        $lastUploadResponse = $this->get(
            '/api/media?q=last',
            ['HTTP_Authorization' => 'Bearer ' . $token]
        );
        $lastUploadResponse->assertJson(['url' => $response->headers->get('Location')]);

        // now remove file
        unlink(storage_path('app/private/media/') . $filename);
        $this->removeDirIfEmpty(storage_path('app/private/media'));
    }

    #[Test]
    public function client_can_source_uploads(): void
    {
        Queue::fake();
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
        $filename = Str::chopStart($path, '/storage/media/');

        $sourceUploadResponse = $this->get(
            '/api/media?q=source',
            ['HTTP_Authorization' => 'Bearer ' . $token]
        );
        $sourceUploadResponse->assertJson(['items' => [[
            'url' => $response->headers->get('Location'),
            'mime_type' => 'image/png',
        ]]]);

        // now remove file
        unlink(storage_path('app/private/media/') . $filename);
        $this->removeDirIfEmpty(storage_path('app/private/media'));
    }

    #[Test]
    public function client_can_source_uploads_with_limit(): void
    {
        Queue::fake();
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
        $filename = Str::chopStart($path, '/storage/media/');

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
        unlink(storage_path('app/private/media/') . $filename);
        $this->removeDirIfEmpty(storage_path('app/private/media'));
    }

    #[Test]
    public function media_endpoint_upload_requires_file(): void
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

    #[Test]
    public function error_response_for_unknown_q_value(): void
    {
        $response = $this->get(
            '/api/media?q=unknown',
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertStatus(400);
        $response->assertJson(['error' => 'invalid_request']);
    }

    #[Test]
    public function options_request_returns_cors_response(): void
    {
        $response = $this->options('/api/media');

        $response->assertStatus(200);
        $response->assertHeader('access-control-allow-origin', '*');
    }

    #[Test]
    public function media_endpoint_request_with_invalid_token_returns400_response(): void
    {
        $response = $this->post(
            '/api/media',
            [],
            ['HTTP_Authorization' => 'Bearer abc123']
        );
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token did not pass validation']);
    }

    #[Test]
    public function media_endpoint_request_with_token_with_no_scope_returns400_response(): void
    {
        $response = $this->post(
            '/api/media',
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithNoScope()]
        );
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token has no scopes']);
    }

    #[Test]
    public function media_endpoint_request_with_insufficient_token_scopes_returns401_response(): void
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

    #[Test]
    public function media_endpoint_upload_file(): void
    {
        Queue::fake();
        $file = __DIR__ . '/../aaron.png';

        $response = $this->post(
            '/api/media',
            [
                'file' => new UploadedFile($file, 'aaron.png', 'image/png', null, true),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = Str::chopStart($path, '/storage/');
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/private/') . $filename);
        $this->removeDirIfEmpty(storage_path('app/private/media'));
    }

    #[Test]
    public function media_endpoint_upload_audio_file(): void
    {
        Queue::fake();
        $file = __DIR__ . '/../audio.mp3';

        $response = $this->post(
            '/api/media',
            [
                'file' => new UploadedFile($file, 'audio.mp3', 'audio/mpeg', null, true),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = Str::chopStart($path, '/storage/');
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/private/') . $filename);
        $this->removeDirIfEmpty(storage_path('app/private/media'));
    }

    #[Test]
    public function media_endpoint_upload_video_file(): void
    {
        Queue::fake();
        $file = __DIR__ . '/../video.ogv';

        $response = $this->post(
            '/api/media',
            [
                'file' => new UploadedFile($file, 'video.ogv', 'video/ogg', null, true),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = Str::chopStart($path, '/storage/');
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/private/') . $filename);
        $this->removeDirIfEmpty(storage_path('app/private/media'));
    }

    #[Test]
    public function media_endpoint_upload_document_file(): void
    {
        Queue::fake();

        $response = $this->post(
            '/api/media',
            [
                'file' => UploadedFile::fake()->create('document.pdf', 100),
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );

        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $filename = Str::chopStart($path, '/storage/');
        Queue::assertPushed(ProcessMedia::class);
        Storage::disk('local')->assertExists($filename);
        // now remove file
        unlink(storage_path('app/private/') . $filename);
        $this->removeDirIfEmpty(storage_path('app/private/media'));
    }

    #[Test]
    public function media_endpoint_upload_invalid_file_returns_error(): void
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
