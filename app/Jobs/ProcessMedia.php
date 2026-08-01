<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Image\ImageException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ProcessMedia implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $filename
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load file
        $file = Storage::disk('local')->get($this->filename);

        // Open file
        $image = Image::fromStorage($this->filename, 'local');
        try {
            $width = $image->width();
        } catch (ImageException) {
            // not an image; delete file and end job
            Storage::disk('local')->delete($this->filename);

            return;
        }

        // Save the file publicly
        Storage::disk('public')->put($this->filename, $file);

        // Create smaller versions if necessary
        if ($width > 1000) {
            $filenameParts = explode('.', $this->filename);
            $extension = array_pop($filenameParts);
            // the following achieves this data flow
            // foo.bar.png => ['foo', 'bar', 'png'] => ['foo', 'bar'] => foo.bar
            $basename = trim(implode('.', $filenameParts), '.');

            Storage::disk('public')->put($basename.'-medium.'.$extension, $image->scale(width: 1000)->toBytes());
            Storage::disk('public')->put($basename.'-small.'.$extension, $image->scale(width: 500)->toBytes());
        }

        // Now we can delete the locally saved image
        Storage::disk('local')->delete($this->filename);
    }
}
