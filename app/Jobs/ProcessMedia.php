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
        //open file
        try {
            $image = $manager->read(storage_path('app') . '/' . $this->filename);
        } catch (DecoderException) {
            // not an image; delete file and end job
            unlink(storage_path('app') . '/' . $this->filename);

            return;
        }
        //create smaller versions if necessary
        if ($image->width() > 1000) {
            $filenameParts = explode('.', $this->filename);
            $extension = array_pop($filenameParts);
            // the following achieves this data flow
            // foo.bar.png => ['foo', 'bar', 'png'] => ['foo', 'bar'] => foo.bar
            $basename = ltrim(array_reduce($filenameParts, function ($carry, $item) {
                return $carry . '.' . $item;
            }, ''), '.');
            $medium = $image->resize(1000, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            Storage::disk('s3')->put('media/' . $basename . '-medium.' . $extension, (string) $medium->encode());
            $small = $image->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            Storage::disk('s3')->put('media/' . $basename . '-small.' . $extension, (string) $small->encode());
        }

        // now we can delete the locally saved image
        unlink(storage_path('app') . '/' . $this->filename);
    }
}
