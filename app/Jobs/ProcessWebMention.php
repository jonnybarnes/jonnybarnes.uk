<?php

namespace App\Jobs;

use App\Note;
use Mf2\parse;
use HTMLPurifier;
use App\WebMention;
use GuzzleHttp\Client;
use HTMLPurifier_Config;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Jonnybarnes\WebmentionsParser\Parser;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessWebMention extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $note;
    protected $source;

    /**
     * Create a new job instance.
     *
     * @param  \App\Note $note
     * @param  string $source
     * @return void
     */
    public function __construct(Note $note, $source)
    {
        $this->note = $note;
        $this->source = $source;
    }

    /**
     * Execute the job.
     *
     * @param  \Jonnybarnes\WebmentionsParser\Parser $parser
     * @return void
     */
    public function handle(Parser $parser)
    {
        $sourceURL = parse_url($this->source);
        $baseURL = $sourceURL['scheme'] . '://' . $sourceURL['host'];
        $remoteContent = $this->getRemoteContent($this->source);
        $microformats = $this->parseHTML($remoteContent, $baseURL);
        $count = WebMention::where('source', '=', $this->source)->count();
        if ($count > 0) {
            //we already have a webmention from this source
            $webmentions = WebMention::where('source', '=', $this->source)->get();
            foreach ($webmentions as $webmention) {
                //now check it still 'mentions' this target
                //we switch for each type of mention (reply/like/repost)
                switch ($webmention->type) {
                    case 'reply':
                        if ($parser->checkInReplyTo($microformats, $note->longurl) == false) {
                            //it doesn't so delete
                            $webmention->delete();

                            return true;
                        }
                        //webmenion is still a reply, so update content
                        $content = $parser->replyContent($microformats);
                        $this->saveImage($content);
                        $content['reply'] = $this->filterHTML($content['reply']);
                        $content = serialize($content);
                        $webmention->content = $content;
                        $webmention->save();

                        return true;
                        break;
                    case 'like':
                        if ($parser->checkLikeOf($microformats, $note->longurl) == false) {
                            //it doesn't so delete
                            $webmention->delete();

                            return true;
                        } //note we don't need to do anything if it still is a like
                        break;
                    case 'repost':
                        if ($parser->checkRepostOf($microformats, $note->longurl) == false) {
                            //it doesn't so delete
                            $webmention->delete();

                            return true;
                        } //again, we don't need to do anything if it still is a repost
                        break;
                }//switch
            }//foreach
        }//if
        //no wemention in db so create new one
        $webmention = new WebMention();
        //check it is in fact a reply
        if ($parser->checkInReplyTo($microformats, $note->longurl)) {
            $content = $parser->replyContent($microformats);
            $this->saveImage($content);
            $content['reply'] = $this->filterHTML($content['reply']);
            $content = serialize($content);
            $webmention->source = $this->source;
            $webmention->target = $note->longurl;
            $webmention->commentable_id = $this->note->id;
            $webmention->commentable_type = 'App\Note';
            $webmention->type = 'reply';
            $webmention->content = $content;
            $webmention->save();

            return true;
        } elseif ($parser->checkLikeOf($microformats, $note->longurl)) {
            //it is a like
            $content = $parser->likeContent($microformats);
            $this->saveImage($content);
            $content = serialize($content);
            $webmention->source = $this->source;
            $webmention->target = $note->longurl;
            $webmention->commentable_id = $this->note->id;
            $webmention->commentable_type = 'App\Note';
            $webmention->type = 'like';
            $webmention->content = $content;
            $webmention->save();

            return true;
        } elseif ($parser->checkRepostOf($microformats, $note->longurl)) {
            //it is a repost
            $content = $parser->repostContent($microformats);
            $this->saveImage($content);
            $content = serialize($content);
            $webmention->source = $this->source;
            $webmention->target = $note->longurl;
            $webmention->commentable_id = $this->note->id;
            $webmention->commentable_type = 'App\Note';
            $webmention->type = 'repost';
            $webmention->content = $content;
            $webmention->save();

            return true;
        }
    }

    /**
     * Retreive the remote content from a URL, and caches the result.
     *
     * @param  string  The URL to retreive content from
     * @return string  The HTML from the URL
     */
    private function getRemoteContent($url)
    {
        $client = new Client();

        $response = $client->get($url);
        $html = (string) $response->getBody();
        $path = storage_path() . '/HTML/' . $this->createFilenameFromURL($url);
        $this->fileForceContents($path, $html);

        return $html;
    }

    /**
     * Create a file path from a URL. This is used when caching the HTML
     * response.
     *
     * @param  string  The URL
     * @return string  The path name
     */
    private function createFilenameFromURL($url)
    {
        $url = str_replace(['https://', 'http://'], ['', ''], $url);
        if (substr($url, -1) == '/') {
            $url = $url . 'index.html';
        }

        return $url;
    }

    /**
     * Save a file, and create any necessary folders.
     *
     * @param string  The directory to save to
     * @param binary  The file to save
     */
    private function fileForceContents($dir, $contents)
    {
        $parts = explode('/', $dir);
        $name = array_pop($parts);
        $dir = implode('/', $parts);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents("$dir/$name", $contents);
    }

    /**
     * A wrapper function for php-mf2’s parse method.
     *
     * @param  string  The HTML to parse
     * @param  string  The base URL to resolve relative URLs in the HTML against
     * @return array   The porcessed microformats
     */
    private function parseHTML($html, $baseurl)
    {
        $microformats = \Mf2\parse((string) $html, $baseurl);

        return $microformats;
    }

    /**
     * Save a profile image to the local cache.
     *
     * @param  array  source content
     * @return bool   wether image was saved or not (we don’t save twitter profiles)
     */
    public function saveImage(array $content)
    {
        $photo = $content['photo'];
        $home = $content['url'];
        //dont save pbs.twimg.com links
        if (parse_url($photo)['host'] != 'pbs.twimg.com'
              && parse_url($photo)['host'] != 'twitter.com') {
            $client = new Client();
            try {
                $response = $client->get($photo);
                $image = $response->getBody(true);
                $path = public_path() . '/assets/profile-images/' . parse_url($home)['host'] . '/image';
                $this->fileForceContents($path, $image);
            } catch (Exception $e) {
                // we are openning and reading the default image so that
                // fileForceContent work
                $default = public_path() . '/assets/profile-images/default-image';
                $handle = fopen($default, 'rb');
                $image = fread($handle, filesize($default));
                fclose($handle);
                $path = public_path() . '/assets/profile-images/' . parse_url($home)['host'] . '/image';
                $this->fileForceContents($path, $image);
            }

            return true;
        }

        return false;
    }

    /**
     * Purify HTML received from a webmention.
     *
     * @param  string  The HTML to be processed
     * @return string  The processed HTML
     */
    public function filterHTML($html)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path() . '/HTMLPurifier');
        $purifier = new HTMLPurifier($config);

        return $purifier->purify($html);
    }
}
