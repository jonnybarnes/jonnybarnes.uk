<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Note;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SyndicateNoteToMastodon implements ShouldQueue
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
     */
    public function handle(): void
    {
        // no ->throw()/->retry() here — Bridgy would duplicate-publish on retry, see $tries above
        $response = Http::acceptJson()->asForm()->post('https://brid.gy/publish/webmention', [
            'source' => $this->note->uri,
            'target' => 'https://brid.gy/publish/mastodon',
        ]);

        $body = $response->json();

        if ($response->status() === 201) {
            $this->note->mastodon_url = $body['url'];
            $this->note->save();

            return;
        }

        throw new \RuntimeException(
            'Bridgy publish to Mastodon failed: '.($body['error'] ?? $response->body())
        );
    }
}
