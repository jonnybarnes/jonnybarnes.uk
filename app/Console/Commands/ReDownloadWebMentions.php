<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DownloadWebMention;
use App\Models\WebMention;
use Illuminate\Console\Command;

/**
 * @psalm-suppress UnusedClass
 */
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
     * Execute the console command.
     */
    public function handle(): void
    {
        $webmentions = WebMention::all();
        foreach ($webmentions as $webmention) {
            $this->info('Initiation re-download of ' . $webmention->source);
            dispatch(new DownloadWebMention($webmention->source));
        }
    }
}
