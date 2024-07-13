<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Note;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyndicateNoteToMastodon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Note $note
    ) {}

    /**
     * Execute the job.
     *
     * @throws GuzzleException
     */
    public function handle(Client $guzzle): void
    {
        // We can only make the request if we have an access token
        if (config('bridgy.mastodon_token') === null) {
            return;
        }

        // Make micropub request
        $response = $guzzle->request(
            'POST',
            'https://brid.gy/micropub',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('bridgy.mastodon_token'),
                ],
                'json' => [
                    'type' => ['h-entry'],
                    'properties' => [
                        'content' => [$this->note->getRawOriginal('note')],
                    ],
                ],
            ]
        );

        // Parse for syndication URL
        if ($response->getStatusCode() === 201) {
            $mastodonUrl = $response->getHeader('Location')[0];
            $this->note->mastodon_url = $mastodonUrl;
            $this->note->save();
        }
    }
}
