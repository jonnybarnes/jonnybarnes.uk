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

class SyndicateNoteToBluesky implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Only try once — retrying would send Bridgy a duplicate publish webmention.
     */
    public int $tries = 1;

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
        $response = $guzzle->request(
            'POST',
            'https://brid.gy/publish/webmention',
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'source' => $this->note->uri,
                    'target' => 'https://brid.gy/publish/bluesky',
                ],
                'http_errors' => false,
            ]
        );

        $body = json_decode((string) $response->getBody(), true);

        if ($response->getStatusCode() === 201) {
            $this->note->bluesky_url = $body['url'];
            $this->note->save();

            return;
        }

        throw new \RuntimeException(
            'Bridgy publish to Bluesky failed: '.($body['error'] ?? (string) $response->getBody())
        );
    }
}
