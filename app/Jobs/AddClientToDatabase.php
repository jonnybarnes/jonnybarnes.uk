<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MicropubClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddClientToDatabase implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected string $client_id;

    /**
     * Create a new job instance.
     */
    public function __construct(string $clientId)
    {
        $this->client_id = $clientId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (MicropubClient::where('client_url', $this->client_id)->count() === 0) {
            MicropubClient::create([
                'client_url' => $this->client_id,
                'client_name' => $this->client_id, // default client name is the URL
            ]);
        }
    }
}
