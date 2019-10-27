<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Bookmark;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyndicateBookmarkToTwitter implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var Bookmark */
    protected $bookmark;

    /**
     * Create a new job instance.
     *
     * @param Bookmark $bookmark
     */
    public function __construct(Bookmark $bookmark)
    {
        $this->bookmark = $bookmark;
    }

    /**
     * Execute the job.
     *
     * @param Client $guzzle
     * @throws GuzzleException
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
                    'target' => 'https://brid.gy/publish/twitter',
                    'bridgy_omit_link' => 'maybe',
                ],
            ]
        );
        //parse for syndication URL
        if ($response->getStatusCode() == 201) {
            $json = json_decode((string) $response->getBody());
            $syndicates = $this->bookmark->syndicates;
            $syndicates['twitter'] = $json->url;
            $this->bookmark->syndicates = $syndicates;
            $this->bookmark->save();
        }
    }
}
