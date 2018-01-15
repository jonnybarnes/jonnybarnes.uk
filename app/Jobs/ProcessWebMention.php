<?php

declare(strict_types=1);

namespace App\Jobs;

use Mf2;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use App\Models\{Note, WebMention};
use Jonnybarnes\WebmentionsParser\Parser;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Exceptions\RemoteContentNotFoundException;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class ProcessWebMention implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $note;
    protected $source;

    /**
     * Create a new job instance.
     *
     * @param  \App\Note  $note
     * @param  string  $source
     */
    public function __construct(Note $note, $source)
    {
        $this->note = $note;
        $this->source = $source;
    }

    /**
     * Execute the job.
     *
     * @param  \Jonnybarnes\WebmentionsParser\Parser  $parser
     * @param  \GuzzleHttp\Client  $guzzle
     */
    public function handle(Parser $parser, Client $guzzle)
    {
        try {
            $response = $guzzle->request('GET', $this->source);
        } catch (RequestException $e) {
            throw new RemoteContentNotFoundException;
        }
        $this->saveRemoteContent((string) $response->getBody(), $this->source);
        $microformats = Mf2\parse((string) $response->getBody(), $this->source);
        $webmentions = WebMention::where('source', $this->source)->get();
        foreach ($webmentions as $webmention) {
            // check webmention still references target
            // we try each type of mention (reply/like/repost)
            if ($webmention->type == 'in-reply-to') {
                if ($parser->checkInReplyTo($microformats, $this->note->longurl) == false) {
                    // it doesn’t so delete
                    $webmention->delete();

                    return;
                }
                // webmenion is still a reply, so update content
                dispatch(new SaveProfileImage($microformats));
                $webmention->mf2 = json_encode($microformats);
                $webmention->save();

                return;
            }
            if ($webmention->type == 'like-of') {
                if ($parser->checkLikeOf($microformats, $note->longurl) == false) {
                    // it doesn’t so delete
                    $webmention->delete();

                    return;
                } // note we don’t need to do anything if it still is a like
            }
            if ($webmention->type == 'repost-of') {
                if ($parser->checkRepostOf($microformats, $note->longurl) == false) {
                    // it doesn’t so delete
                    $webmention->delete();

                    return;
                } // again, we don’t need to do anything if it still is a repost
            }
        }// foreach

        // no webmention in the db so create new one
        $webmention = new WebMention();
        $type = $parser->getMentionType($microformats); // throw error here?
        dispatch(new SaveProfileImage($microformats));
        $webmention->source = $this->source;
        $webmention->target = $this->note->longurl;
        $webmention->commentable_id = $this->note->id;
        $webmention->commentable_type = 'App\Note';
        $webmention->type = $type;
        $webmention->mf2 = json_encode($microformats);
        $webmention->save();
    }

    /**
     * Save the HTML of a webmention for future use.
     *
     * @param  string  $html
     * @param  string  $url
     */
    private function saveRemoteContent($html, $url)
    {
        $filenameFromURL = str_replace(
            ['https://', 'http://'],
            ['https/', 'http/'],
            $url
        );
        if (substr($url, -1) == '/') {
            $filenameFromURL .= 'index.html';
        }
        $path = storage_path() . '/HTML/' . $filenameFromURL;
        $parts = explode('/', $path);
        $name = array_pop($parts);
        $dir = implode('/', $parts);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents("$dir/$name", $html);
    }
}
