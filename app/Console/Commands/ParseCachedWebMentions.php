<?php

namespace App\Console\Commands;

use App\WebMention;
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
    public function handle(FileSystem $filesystem)
    {
        $HTMLfiles = $filesystem->allFiles(storage_path() . '/HTML');
        foreach ($HTMLfiles as $file) {
            if ($file->getExtension() != 'backup') { //we don’t want to parse.backup files
                $filepath = $file->getPathname();
                $this->info('Loading HTML from: ' . $filepath);
                $html = $filesystem->get($filepath);
                $url = $this->URLFromFilename($filepath);
                $microformats = \Mf2\parse($html, $url);
                $webmention = WebMention::where('source', $url)->firstOrFail();
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
    private function URLFromFilename($filepath)
    {
        $dir = mb_substr($filepath, mb_strlen(storage_path() . '/HTML/'));
        $url = str_replace(['http/', 'https/'], ['http://', 'https://'], $dir);
        if (mb_substr($url, -1) == 'index.html') {
            $url = mb_substr($url, 0, mb_strlen($url) - 10);
        }

        return $url;
    }
}
