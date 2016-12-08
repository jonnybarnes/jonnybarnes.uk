<?php

namespace App\Console\Commands;

use App\Services\TokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a token that can be used for testing purposes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The token service class.
     *
     * @var TokenService
     */
    protected $tokenService;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(TokenService $tokenService)
    {
        $data = [
            'me' => env('APP_URL'),
            'client_id' => env('APP_URL') . '/notes/new',
            'scope' => 'post',
        ];
        $token = $tokenService->getNewToken($data);
        Storage::disk('local')->put('dev-token', $token);

        $this->info('Set token');
    }
}
