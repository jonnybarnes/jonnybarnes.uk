<?php

namespace Tests\Feature;

use Storage;
use Tests\TestCase;
use Intervention\Image\ImageManager;

class ProcessImageTest extends TestCase
{
    public function test_job_does_nothing_to_non_image()
    {
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('file.txt', 'This is not an image');
        $job = new \App\Jobs\ProcessImage('file.txt');
        $job->handle($manager);

        $this->assertFalse(file_exists(storage_path('app') . '/file.txt'));
    }

    public function test_job_does_nothing_to_small_images()
    {
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('aaron.png', file_get_contents(__DIR__.'/../aaron.png'));
        $job = new \App\Jobs\ProcessImage('aaron.png');
        $job->handle($manager);

        $this->assertFalse(file_exists(storage_path('app') . '/aaron.png'));
    }

    public function test_large_images_have_smaller_files_created()
    {
        $manager = app()->make(ImageManager::class);
        Storage::disk('local')->put('test-image.jpg', file_get_contents(__DIR__.'/../test-image.jpg'));
        Storage::fake('s3');
        $job = new \App\Jobs\ProcessImage('test-image.jpg');
        $job->handle($manager);

        Storage::disk('s3')->assertExists('media/test-image-small.jpg');
        Storage::disk('s3')->assertExists('media/test-image-medium.jpg');
        $this->assertFalse(file_exists(storage_path('app') . '/test-image.jpg'));
    }
}
