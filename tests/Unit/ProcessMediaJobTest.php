<?php

namespace Tests\Unit;

use Storage;
use Tests\TestCase;
use App\Jobs\ProcessMedia;
use Intervention\Image\ImageManager;

class ProcessMediaJobTest extends TestCase
{
    public function test_job_does_nothing_to_non_image()
    {
        Storage::fake('s3');
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('file.txt', 'This is not an image');
        $job = new ProcessMedia('file.txt');
        $job->handle($manager);

        $this->assertFalse(file_exists(storage_path('app') . '/file.txt'));
    }

    public function test_job_does_nothing_to_small_images()
    {
        Storage::fake('s3');
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('aaron.png', file_get_contents(__DIR__.'/../aaron.png'));
        $job = new ProcessMedia('aaron.png');
        $job->handle($manager);

        $this->assertFalse(file_exists(storage_path('app') . '/aaron.png'));
    }

    public function test_large_images_have_smaller_files_created()
    {
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('test-image.jpg', file_get_contents(__DIR__.'/../test-image.jpg'));
        Storage::fake('s3');
        $job = new ProcessMedia('test-image.jpg');
        $job->handle($manager);

        Storage::disk('s3')->assertExists('media/test-image-small.jpg');
        Storage::disk('s3')->assertExists('media/test-image-medium.jpg');
        $this->assertFalse(file_exists(storage_path('app') . '/test-image.jpg'));
    }
}
