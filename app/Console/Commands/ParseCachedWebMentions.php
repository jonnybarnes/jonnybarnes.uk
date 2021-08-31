<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebMention;
use Illuminate\Console\Command;
use Illuminate\FileSystem\FileSystem;

class ParseCachedWebMentions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webmentions:parsecached';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-parse the webmention’s cached HTML';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(FileSystem $filesystem)
    {
        $htmlFiles = $filesystem->allFiles(storage_path() . '/HTML');
        foreach ($htmlFiles as $file) {
            if ($file->getExtension() !== 'backup') { //we don’t want to parse `.backup` files
                $filepath = $file->getPathname();
                $this->info('Loading HTML from: ' . $filepath);
                $html = $filesystem->get($filepath);
                $url = $this->urlFromFilename($filepath);
                $webmention = WebMention::where('source', $url)->firstOrFail();
                $microformats = \Mf2\parse($html, $url);
                $webmention->mf2 = json_encode($microformats);
                $webmention->save();
                $this->info('Saved the microformats to the database.');
            }
        }
    }

    /**
     * Determine the source URL from a filename.
     *
     * @param  string
     * @return string
     */
    private function urlFromFilename(string $filepath): string
    {
        $dir = mb_substr($filepath, mb_strlen(storage_path() . '/HTML/'));
        $url = str_replace(['http/', 'https/'], ['http://', 'https://'], $dir);
        if (mb_substr($url, -10) === 'index.html') {
            $url = mb_substr($url, 0, -10);
        }

        return $url;
    }
}
