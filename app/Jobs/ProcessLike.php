<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Like;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Thujohn\Twitter\Facades\Twitter;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;

class ProcessLike implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $like;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Like  $like
     */
    public function __construct(Like $like)
    {
        $this->like = $like;
    }

    /**
     * Execute the job.
     *
     * @param  \GuzzleHttp\Client  $client
     * @param  \Jonnybarnes\WebmentionsParser\Authorship  $authorship
     * @return int
     */
    public function handle(Client $client, Authorship $authorship): int
    {
        if ($this->isTweet($this->like->url)) {
            $tweet = Twitter::getOembed(['url' => $this->like->url]);
            $this->like->author_name = $tweet->author_name;
            $this->like->author_url = $tweet->author_url;
            $this->like->content = $tweet->html;
            $this->like->save();

            //POSSE like
            try {
                $response = $client->request(
                    'POST',
                    'https://brid.gy/publish/webmention',
                    [
                        'form_params' => [
                            'source' => $this->like->url,
                            'target' => 'https://brid.gy/publish/twitter',
                        ],
                    ]
                );
            } catch (ClientException $exception) {
                //no biggie
            }

            return 0;
        }

        $response = $client->request('GET', $this->like->url);
        $mf2 = \Mf2\parse((string) $response->getBody(), $this->like->url);
        if (array_has($mf2, 'items.0.properties.content')) {
            $this->like->content = $mf2['items'][0]['properties']['content'][0]['html'];
        }

        try {
            $author = $authorship->findAuthor($mf2);
            if (is_array($author)) {
                $this->like->author_name = array_get($author, 'properties.name.0');
                $this->like->author_url = array_get($author, 'properties.url.0');
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
