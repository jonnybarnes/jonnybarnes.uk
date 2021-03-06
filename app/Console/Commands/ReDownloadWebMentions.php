<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DownloadWebMention;
use App\Models\WebMention;
use Illuminate\Console\Command;

class ReDownloadWebMentions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webmentions:redownload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Redownload the HTML content of webmentions';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $webmentions = WebMention::all();
        foreach ($webmentions as $webmention) {
            $this->info('Initiation re-download of ' . $webmention->source);
            dispatch(new DownloadWebMention($webmention->source));
        }
    }
}
