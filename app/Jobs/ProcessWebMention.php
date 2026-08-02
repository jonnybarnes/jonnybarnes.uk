<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\RemoteContentNotFoundException;
use App\Models\Note;
use App\Models\WebMention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Jonnybarnes\WebmentionsParser\Exceptions\InvalidMentionException;
use Jonnybarnes\WebmentionsParser\Parser;
use Mf2;

class ProcessWebMention implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Note $note,
        protected string $source
    ) {}

    /**
     * Execute the job.
     *
     * @throws RemoteContentNotFoundException
     * @throws InvalidMentionException
     */
    public function handle(Parser $parser): void
    {
        try {
            $response = Http::get($this->source);
        } catch (ConnectionException) {
            throw new RemoteContentNotFoundException;
        }
        if ($response->failed()) {
            throw new RemoteContentNotFoundException;
        }
        $this->saveRemoteContent($response->body(), $this->source);
        $microformats = Mf2\parse($response->body(), $this->source);
        $webmentions = WebMention::where('source', $this->source)->get();
        foreach ($webmentions as $webmention) {
            // check webmention still references target
            // we try each type of mention (reply/like/repost)
            if ($webmention->type === 'in-reply-to') {
                if ($parser->checkInReplyTo($microformats, $this->note->uri) === false) {
                    // it doesn’t so delete
                    $webmention->delete();

                    return;
                }
                // webmention is still a reply, so update content
                dispatch(new SaveProfileImage($microformats));
                $webmention->mf2 = json_encode($microformats);
                $webmention->save();

                return;
            }
            if ($webmention->type === 'like-of') {
                if ($parser->checkLikeOf($microformats, $this->note->uri) === false) {
                    // it doesn’t so delete
                    $webmention->delete();

                    return;
                } // note we don’t need to do anything if it still is a like
            }
            if ($webmention->type === 'repost-of') {
                if ($parser->checkRepostOf($microformats, $this->note->uri) === false) {
                    // it doesn’t so delete
                    $webmention->delete();

                    return;
                } // again, we don’t need to do anything if it still is a repost
            }
        }// foreach

        // no webmention in the db so create new one
        $webmention = new WebMention;
        $type = $parser->getMentionType($microformats); // throw error here?
        dispatch(new SaveProfileImage($microformats));
        $webmention->source = $this->source;
        $webmention->target = $this->note->uri;
        $webmention->commentable_id = $this->note->id;
        $webmention->commentable_type = Note::class;
        $webmention->type = $type;
        $webmention->mf2 = json_encode($microformats);
        $webmention->save();
        dispatch(new NotifyBrrrOfWebMention($webmention));
    }

    /**
     * Save the HTML of a webmention for future use.
     */
    private function saveRemoteContent(string $html, string $url): void
    {
        $filenameFromURL = str_replace(
            ['https://', 'http://'],
            ['https/', 'http/'],
            $url
        );
        if (str_ends_with($url, '/')) {
            $filenameFromURL .= 'index.html';
        }
        $path = storage_path().'/HTML/'.$filenameFromURL;
        $parts = explode('/', $path);
        $name = array_pop($parts);
        $dir = implode('/', $parts);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        file_put_contents("$dir/$name", $html);
    }
}
