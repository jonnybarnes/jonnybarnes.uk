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
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('media/file.txt', 'This is not an image');
        $job = new ProcessMedia('file.txt');
        $job->handle($manager);

        $this->assertFileDoesNotExist(storage_path('app/media/') . 'file.txt');
    }

    /** @test */
    public function smallImagesAreNotResized(): void
    {
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('media/aaron.png', file_get_contents(__DIR__ . '/../../aaron.png'));
        $job = new ProcessMedia('aaron.png');
        $job->handle($manager);

        $this->assertFileDoesNotExist(storage_path('app/media/') . 'aaron.png');

        // Tidy up files created by the job
        Storage::disk('local')->delete('public/media/aaron.png');
        Storage::disk('local')->delete('public/media');
    }

    /** @test */
    public function largeImagesHaveSmallerImagesCreated(): void
    {
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('media/test-image.jpg', file_get_contents(__DIR__.'/../../test-image.jpg'));
        $job = new ProcessMedia('test-image.jpg');
        $job->handle($manager);

        Storage::disk('local')->assertExists('public/media/test-image.jpg');
        Storage::disk('local')->assertExists('public/media/test-image-small.jpg');
        Storage::disk('local')->assertExists('public/media/test-image-medium.jpg');

        $this->assertFileDoesNotExist(storage_path('app/media/') . 'test-image.jpg');

        // Tidy up files created by the job
        Storage::disk('local')->delete('public/media/test-image.jpg');
        Storage::disk('local')->delete('public/media/test-image-small.jpg');
        Storage::disk('local')->delete('public/media/test-image-medium.jpg');
        $this->removeDirIfEmpty(storage_path('app/public/media'));
        $this->removeDirIfEmpty(storage_path('app/media'));
    }
}
