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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $client_id;

    /**
     * Create a new job instance.
     *
     * @param  string  $client_id
     */
    public function __construct(string $client_id)
    {
        $this->client_id = $client_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (MicropubClient::where('client_url', $this->client_id)->count() == 0) {
            $client = MicropubClient::create([
                'client_url' => $this->client_id,
                'client_name' => $this->client_id, // default client name is the URL
            ]);
        }
    }
}
