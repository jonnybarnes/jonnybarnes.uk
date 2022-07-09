<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Like;
use Codebird\Codebird;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;

class ProcessLike implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var Like */
    protected $like;

    /**
     * Create a new job instance.
     *
     * @param  Like  $like
     */
    public function __construct(Like $like)
    {
        $this->like = $like;
    }

    /**
     * Execute the job.
     *
     * @param  Client  $client
     * @param  Authorship  $authorship
     * @return int
     *
     * @throws GuzzleException
     */
    public function handle(Client $client, Authorship $authorship): int
    {
        if ($this->isTweet($this->like->url)) {
            $codebird = resolve(Codebird::class);

            $tweet = $codebird->statuses_oembed(['url' => $this->like->url]);

            $this->like->author_name = $tweet->author_name;
            $this->like->author_url = $tweet->author_url;
            $this->like->content = $tweet->html;
            $this->like->save();

            //POSSE like
            try {
                $client->request(
                    'POST',
                    'https://brid.gy/publish/webmention',
                    [
                        'form_params' => [
                            'source' => $this->like->url,
                            'target' => 'https://brid.gy/publish/twitter',
                        ],
                    ]
                );
            } catch (RequestException) {
                return 0;
            }

            return 0;
        }

        $response = $client->request('GET', $this->like->url);
        $mf2 = \Mf2\parse((string) $response->getBody(), $this->like->url);
        if (Arr::has($mf2, 'items.0.properties.content')) {
            $this->like->content = $mf2['items'][0]['properties']['content'][0]['html'];
        }

        try {
            $author = $authorship->findAuthor($mf2);
            if (is_array($author)) {
                $this->like->author_name = Arr::get($author, 'properties.name.0');
                $this->like->author_url = Arr::get($author, 'properties.url.0');
            }
            if (is_string($author) && $author !== '') {
                $this->like->author_name = $author;
            }
        } catch (AuthorshipParserException $exception) {
            return 1;
        }

        $this->like->save();

        return 0;
    }

    /**
     * Determine if a given URL is that of a Tweet.
     *
     * @param  string  $url
     * @return bool
     */
    private function isTweet(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $parts = array_reverse(explode('.', $host));

        return $parts[0] === 'com' && $parts[1] === 'twitter';
    }
}
