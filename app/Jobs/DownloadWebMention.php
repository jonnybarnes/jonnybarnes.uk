<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\FileSystem\FileSystem;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DownloadWebMention implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $source
    ) {}

    /**
     * Execute the job.
     *
     * @throws RequestException
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        // 4XX and 5XX responses should throw so Laravel can catch and
        // retry these automatically.
        $response = Http::throw()->get($this->source);
        if ($response->status() === 200) {
            $filesystem = new FileSystem;
            $filename = storage_path('HTML').'/'.$this->createFilenameFromURL($this->source);
            // backup file first
            $filenameBackup = $filename.'.'.date('Y-m-d').'.backup';
            if ($filesystem->exists($filename)) {
                $filesystem->copy($filename, $filenameBackup);
            }
            // check if base directory exists
            if (! $filesystem->exists($filesystem->dirname($filename))) {
                $filesystem->makeDirectory(
                    $filesystem->dirname($filename),
                    0755,  // mode
                    true // recursive
                );
            }
            // save new HTML
            $filesystem->put(
                $filename,
                $response->body()
            );
            // remove backup if the same
            if ($filesystem->exists($filenameBackup)) {
                if ($filesystem->get($filename) === $filesystem->get($filenameBackup)) {
                    $filesystem->delete($filenameBackup);
                }
            }
        }
    }

    /**
     * Create a file path from a URL. This is used when caching the HTML response.
     */
    private function createFilenameFromURL(string $url): string
    {
        $filepath = str_replace(['https://', 'http://'], ['https/', 'http/'], $url);
        if (str_ends_with($filepath, '/')) {
            $filepath .= 'index.html';
        }

        return $filepath;
    }
}
