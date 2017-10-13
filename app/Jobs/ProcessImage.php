<?php

namespace App\Jobs;

use Storage;
use Illuminate\Bus\Queueable;
use Intervention\Image\ImageManager;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Intervention\Image\Exception\NotReadableException;

class ProcessImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filename;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ImageManager $manager)
    {
        //open file
        try {
            $image = $manager->make(storage_path('app') . '/' . $this->filename);
        } catch (NotReadableException $exception) {
            // not an image; delete file and end job
            unlink(storage_path('app') . '/' . $this->filename);

            return;
        }
        //create smaller versions if necessary
        if ($image->width() > 1000) {
            $filenameParts = explode('.', $this->filename);
            $extension = array_pop($filenameParts);
            // the following acheives this data flow
            // foo.bar.png => ['foo', 'bar', 'png'] => ['foo', 'bar'] => foo.bar
            $basename = ltrim(array_reduce($filenameParts, function ($carry, $item) {
                return $carry . '.' . $item;
            }, ''), '.');
            $medium = $image->resize(1000, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            Storage::disk('s3')->put('media/'. $basename . '-medium.' . $extension, (string) $medium->encode());
            $small = $image->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            Storage::disk('s3')->put('media/' . $basename . '-small.' . $extension, (string) $small->encode());
        }

        // now we can delete the locally saved image
        unlink(storage_path('app') . '/' . $this->filename);
    }
}
