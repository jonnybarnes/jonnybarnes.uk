<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessMedia;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessMediaJobTest extends TestCase
{
    #[Test]
    public function non_media_files_are_not_saved(): void
    {
        Storage::disk('local')->put('media/file.txt', 'This is not an image');
        $job = new ProcessMedia('media/file.txt');
        $job->handle();

        $this->assertFileDoesNotExist(storage_path('app/media/').'file.txt');
    }

    #[Test]
    public function small_images_are_not_resized(): void
    {
        Storage::disk('local')->put('media/aaron.png', file_get_contents(__DIR__.'/../../aaron.png'));
        $job = new ProcessMedia('media/aaron.png');
        $job->handle();

        $this->assertFileDoesNotExist(storage_path('app/media/').'aaron.png');

        // Tidy up files created by the job
        Storage::disk('local')->delete('public/media/aaron.png');
        Storage::disk('local')->delete('public/media');
    }

    #[Test]
    public function large_images_have_smaller_images_created(): void
    {
        Storage::disk('local')->put('media/test-image.jpg', file_get_contents(__DIR__.'/../../test-image.jpg'));
        $job = new ProcessMedia('media/test-image.jpg');
        $job->handle();

        // These need to look in public disk
        Storage::disk('public')->assertExists('media/test-image.jpg');
        Storage::disk('public')->assertExists('media/test-image-small.jpg');
        Storage::disk('public')->assertExists('media/test-image-medium.jpg');

        $this->assertFileDoesNotExist(storage_path('app/media/').'test-image.jpg');

        // Tidy up files created by the job
        Storage::disk('public')->delete('media/test-image.jpg');
        Storage::disk('public')->delete('media/test-image-small.jpg');
        Storage::disk('public')->delete('media/test-image-medium.jpg');
        $this->removeDirIfEmpty(storage_path('app/public/media'));
        $this->removeDirIfEmpty(storage_path('app/media'));
    }
}
