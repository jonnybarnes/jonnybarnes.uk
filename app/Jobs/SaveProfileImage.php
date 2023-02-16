<?php

declare(strict_types=1);

namespace App\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;

class SaveProfileImage implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected array $microformats;

    /**
     * Create a new job instance.
     */
    public function __construct(array $microformats)
    {
        $this->microformats = $microformats;
    }

    /**
     * Execute the job.
     */
    public function handle(Authorship $authorship)
    {
        try {
            $author = $authorship->findAuthor($this->microformats);
        } catch (AuthorshipParserException) {
            return null;
        }

        $photo = Arr::get($author, 'properties.photo.0');
        $home = Arr::get($author, 'properties.url.0');

        //dont save pbs.twimg.com links
        if (
            $photo
            && parse_url($photo, PHP_URL_HOST) != 'pbs.twimg.com'
            && parse_url($photo, PHP_URL_HOST) != 'twitter.com'
        ) {
            $client = resolve(Client::class);

            try {
                $response = $client->get($photo);
                $image = $response->getBody();
            } catch (RequestException) {
                // we are opening and reading the default image so that
                $default = public_path() . '/assets/profile-images/default-image';
                $handle = fopen($default, 'rb');
                $image = fread($handle, filesize($default));
                fclose($handle);
            }

            $path = public_path() . '/assets/profile-images/' . parse_url($home, PHP_URL_HOST) . '/image';
            $parts = explode('/', $path);
            $name = array_pop($parts);
            $dir = implode('/', $parts);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents("$dir/$name", $image);
        }
    }
}
