<?php

namespace App\Jobs;

use App\Bookmark;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyndicateBookmarkToFacebook implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $bookmark;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Bookmark $bookmark)
    {
        $this->bookmark = $bookmark;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Client $guzzle)
    {
        //send webmention
        $response = $guzzle->request(
            'POST',
            'https://brid.gy/publish/webmention',
            [
                'form_params' => [
                    'source' => $this->bookmark->longurl,
                    'target' => 'https://brid.gy/publish/facebook',
                    'bridgy_omit_link' => 'maybe',
                ],
            ]
        );
        //parse for syndication URL
        if ($response->getStatusCode() == 201) {
            $json = json_decode((string) $response->getBody());
            $syndicates = $this->bookmark->syndicates;
            $syndicates['facebook'] = $json->url;
            $this->bookmark->syndicates = $syndicates;
            $this->bookmark->save();
        }
    }
}
