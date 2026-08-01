<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;

class SaveProfileImage implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $microformats
    ) {}

    /**
     * Execute the job.
     */
    public function handle(Authorship $authorship): void
    {
        try {
            $author = $authorship->findAuthor($this->microformats);
        } catch (AuthorshipParserException) {
            return;
        }

        $photo = Arr::get($author, 'properties.photo.0');
        $home = Arr::get($author, 'properties.url.0');

        if (is_array($photo) && array_key_exists('value', $photo)) {
            $photo = $photo['value'];
        }

        if (is_array($home)) {
            $home = array_shift($home);
        }

        // dont save pbs.twimg.com links
        if (
            $photo
            && parse_url($photo, PHP_URL_HOST) !== 'pbs.twimg.com'
            && parse_url($photo, PHP_URL_HOST) !== 'twitter.com'
        ) {
            try {
                $response = Http::throw()->get($photo);
                $image = $response->body();
            } catch (ConnectionException|RequestException) {
                // we are opening and reading the default image so that
                $default = public_path().'/assets/profile-images/default-image';
                $handle = fopen($default, 'rb');
                $image = fread($handle, filesize($default));
                fclose($handle);
            }

            $path = public_path().'/assets/profile-images/'.parse_url($home, PHP_URL_HOST).'/image';
            $parts = explode('/', $path);
            $name = array_pop($parts);
            $dir = implode('/', $parts);
            if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
            file_put_contents("$dir/$name", $image);
        }
    }
}
