<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\MicropubClient;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AddClientToDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $client_id;

    /**
     * Create a new job instance.
     *
     * @return void
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
