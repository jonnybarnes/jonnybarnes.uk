<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CopyMediaToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:copy-media-to-local';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy any historic media saved to S3 to the local filesystem';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Load all the Media records
        $media = Media::all();

        // Loop through each media record and copy the file from S3 to the local filesystem
        foreach ($media as $mediaItem) {
            $filename = $mediaItem->path;

            $this->info('Processing: ' . $filename);

            // If the file is already saved locally skip to next one
            if (Storage::disk('local')->exists('public/' . $filename)) {
                $this->info('File already exists locally, skipping');

                continue;
            }

            // Copy the file from S3 to the local filesystem
            if (! Storage::disk('s3')->exists($filename)) {
                $this->error('File does not exist on S3');

                continue;
            }
            $contents = Storage::disk('s3')->get($filename);
            Storage::disk('local')->put('public/' . $filename, $contents);

            // Copy -medium and -small versions if they exist
            $filenameParts = explode('.', $filename);
            $extension = array_pop($filenameParts);
            $basename = trim(implode('.', $filenameParts), '.');
            $mediumFilename = $basename . '-medium.' . $extension;
            $smallFilename = $basename . '-small.' . $extension;
            if (Storage::disk('s3')->exists($mediumFilename)) {
                Storage::disk('local')->put('public/' . $mediumFilename, Storage::disk('s3')->get($mediumFilename));
            }
            if (Storage::disk('s3')->exists($smallFilename)) {
                Storage::disk('local')->put('public/' . $smallFilename, Storage::disk('s3')->get($smallFilename));
            }
        }
    }
}
