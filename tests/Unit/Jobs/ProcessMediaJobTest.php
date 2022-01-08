<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessMedia;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class ProcessMediaJobTest extends TestCase
{
    /** @test */
    public function nonMediaFilesAreNotSaved(): void
    {
        Storage::fake('s3');
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('file.txt', 'This is not an image');
        $job = new ProcessMedia('file.txt');
        $job->handle($manager);

        $this->assertFalse(file_exists(storage_path('app') . '/file.txt'));
    }

    /** @test */
    public function smallImagesAreNotResized(): void
    {
        Storage::fake('s3');
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('aaron.png', file_get_contents(__DIR__ . '/../../aaron.png'));
        $job = new ProcessMedia('aaron.png');
        $job->handle($manager);

        $this->assertFalse(file_exists(storage_path('app') . '/aaron.png'));
    }

    /** @test */
    public function largeImagesHaveSmallerImagesCreated(): void
    {
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('test-image.jpg', file_get_contents(__DIR__.'/../../test-image.jpg'));
        Storage::fake('s3');
        $job = new ProcessMedia('test-image.jpg');
        $job->handle($manager);

        Storage::disk('s3')->assertExists('media/test-image-small.jpg');
        Storage::disk('s3')->assertExists('media/test-image-medium.jpg');
        $this->assertFalse(file_exists(storage_path('app') . '/test-image.jpg'));
    }
}
