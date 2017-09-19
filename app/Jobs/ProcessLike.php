<?php

namespace App\Jobs;

use App\Like;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
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
     * @return void
     */
    public function __construct(Like $like)
    {
        $this->like = $like;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Client $client, Authorship $authorship)
    {
        $response = $client->request('GET', $this->like->url);
        $mf2 = \Mf2\parse((string) $response->getBody(), $this->like->url);
        if (array_has($mf2, 'items.0.properties.content')) {
            $this->like->content = $mf2['items'][0]['properties']['content'][0]['html'];
        }

        try {
            $author = $authorship->findAuthor($mf2);
            if (is_array($author)) {
                $this->like->author_name = $author['name'];
                $this->like->author_url = $author['url'];
            }
            if (is_string($author) && $author !== '') {
                $this->like->author_name = $author;
            }
        } catch (AuthorshipParserException $exception) {
            return;
        }

        $this->like->save();
    }
}
