<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Like;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;

class ProcessLike implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Like $like
    ) {}

    /**
     * Execute the job.
     */
    public function handle(Authorship $authorship): int
    {
        $response = Http::throw()->get($this->like->url);
        $mf2 = \Mf2\parse($response->body(), $this->like->url);
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
}
