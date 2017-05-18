<?php

namespace App\Console\Commands;

use App\IndieWebUser;
use App\Services\TokenService;
use Illuminate\Console\Command;

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
            'me' => config('app.url'),
            'client_id' => route('micropub-client'),
            'scope' => 'create update',
        ];
        $token = $tokenService->getNewToken($data);
        $user = IndieWebUser::where('me', config('app.url'))->first();
        $user->token = $token;
        $user->save();

        $this->info('Set token');
    }
}
