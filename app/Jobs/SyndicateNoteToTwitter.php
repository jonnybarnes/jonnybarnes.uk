<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Note;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyndicateNoteToTwitter implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var Note */
    protected $note;

    /**
     * Create a new job instance.
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    /**
     * Execute the job.
     *
     *
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
                    'source' => $this->note->longurl,
                    'target' => 'https://brid.gy/publish/twitter',
                    'bridgy_omit_link' => 'maybe',
                ],
            ]
        );
        //parse for syndication URL
        if ($response->getStatusCode() == 201) {
            $json = json_decode((string) $response->getBody());
            $tweet_id = basename(parse_url($json->url, PHP_URL_PATH));
            $this->note->tweet_id = $tweet_id;
            $this->note->save();
        }
    }
}
