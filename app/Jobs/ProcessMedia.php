<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;

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
    public function handle(ImageManager $manager): void
    {
        // Load file
        $file = Storage::disk('local')->get($this->filename);

        // Open file
        try {
            $image = $manager->read($file);
        } catch (DecoderException) {
            // not an image; delete file and end job
            Storage::disk('local')->delete($this->filename);

            return;
        }

        // Save the file publicly
        Storage::disk('public')->put($this->filename, $file);

        // Create smaller versions if necessary
        if ($image->width() > 1000) {
            $filenameParts = explode('.', $this->filename);
            $extension = array_pop($filenameParts);
            // the following achieves this data flow
            // foo.bar.png => ['foo', 'bar', 'png'] => ['foo', 'bar'] => foo.bar
            $basename = trim(implode('.', $filenameParts), '.');

            $medium = $image->resize(width: 1000);
            Storage::disk('public')->put($basename.'-medium.'.$extension, (string) $medium->encode());

            $small = $image->resize(width: 500);
            Storage::disk('public')->put($basename.'-small.'.$extension, (string) $small->encode());
        }

        // Now we can delete the locally saved image
        Storage::disk('local')->delete($this->filename);
    }
}
