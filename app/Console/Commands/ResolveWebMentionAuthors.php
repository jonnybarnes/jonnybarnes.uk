<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebMention;
use App\Services\WebMention\Authorship;
use Illuminate\Console\Command;

class ResolveWebMentionAuthors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webmentions:resolve-authors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve and store the author of each webmention from its microformats';

    /**
     * Execute the console command.
     */
    public function handle(Authorship $authorship): void
    {
        $resolved = 0;
        $unknown = 0;

        WebMention::whereNotNull('mf2')->lazyById()->each(function (WebMention $webmention) use ($authorship, &$resolved, &$unknown) {
            $author = $authorship->resolve(json_decode($webmention->mf2, true) ?? []);
            $webmention->author = $author;
            $webmention->saveQuietly();

            $author === null ? $unknown++ : $resolved++;
        });

        $this->info("Resolved {$resolved} authors, {$unknown} unknown.");
    }
}
