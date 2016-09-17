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
        $response = $guzzle->request('GET', $source);
        //4XX and 5XX responses should get Guzzle to throw an exception,
        //Laravel should catch and retry these automatically.
        if ($response->getStatusCode() == '200') {
            $filesystem = \Illuminate\FileSystem\FileSystem();
            $filesystem->put(
                $this->createFilenameFromURL($source),
                (string) $response->getBody())
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
        $url = str_replace(['https://', 'http://'], ['https/', 'http/'], $url);
        if (substr($url, -1) == '/') {
            $url = $url . 'index.html';
        }

        return $url;
    }
}
