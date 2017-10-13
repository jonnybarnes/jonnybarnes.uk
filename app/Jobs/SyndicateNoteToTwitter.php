<?php

namespace App\Jobs;

use App\Note;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyndicateNoteToTwitter implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $note;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    /**
     * Execute the job.
     *
     * @param  \GuzzleHttp\Client $guzzle
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
