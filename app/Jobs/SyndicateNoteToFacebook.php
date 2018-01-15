<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Note;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyndicateNoteToFacebook implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $note;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Note  $note
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    /**
     * Execute the job.
     *
     * @param  \GuzzleHttp\Client  $guzzle
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
                    'target' => 'https://brid.gy/publish/facebook',
                    'bridgy_omit_link' => 'maybe',
                ],
            ]
        );
        //parse for syndication URL
        if ($response->getStatusCode() == 201) {
            $json = json_decode((string) $response->getBody());
            $this->note->facebook_url = $json->url;
            $this->note->save();
        }
    }
}
