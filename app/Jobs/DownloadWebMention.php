<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DownloadWebMention implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The webmention source URL.
     *
     * @var
     */
    protected $source;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $source)
    {
        $this->source = $source;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Client $guzzle)
    {
        $response = $guzzle->request('GET', $this->source);
        //4XX and 5XX responses should get Guzzle to throw an exception,
        //Laravel should catch and retry these automatically.
        if ($response->getStatusCode() == '200') {
            $filesystem = new \Illuminate\FileSystem\FileSystem();
            $filename = storage_path() . '/HTML/' . $this->createFilenameFromURL($this->source);
            //backup file first
            $filenameBackup = $filename . '.' . date('Y-m-d') . '.backup';
            if ($filesystem->exists($filename)) {
                $filesystem->copy($filename, $filenameBackup);
            }
            //check if base directory exists
            if (! $filesystem->exists($filesystem->dirname($filename))) {
                $filesystem->makeDirectory(
                    $filesystem->dirname($filename),
                    0755,  //mode
                    true //recursive
                );
            }
            //save new HTML
            $filesystem->put(
                $filename,
                (string) $response->getBody()
            );
            //remove backup if the same
            if ($filesystem->exists($filenameBackup)) {
                if ($filesystem->get($filename) == $filesystem->get($filenameBackup)) {
                    $filesystem->delete($filenameBackup);
                }
            }
        }
    }

    /**
     * Create a file path from a URL. This is used when caching the HTML
     * response.
     *
     * @param  string  The URL
     * @return string  The path name
     */
    private function createFilenameFromURL($url)
    {
        $filepath = str_replace(['https://', 'http://'], ['https/', 'http/'], $url);
        if (substr($filepath, -1) == '/') {
            $filepath .= 'index.html';
        }

        return $filepath;
    }
}
