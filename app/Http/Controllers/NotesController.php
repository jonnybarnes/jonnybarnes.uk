<?php

namespace App\Http\Controllers;

use Twitter;
use HTMLPurifier;
use App\{Note, Tag};
use GuzzleHttp\Client;
use HTMLPurifier_Config;
use Illuminate\Http\Request;
use Jonnybarnes\IndieWeb\Numbers;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Jonnybarnes\WebmentionsParser\Authorship;

// Need to sort out Twitter and webmentions!

class NotesController extends Controller
{
    /**
     * Show all the notes.
     *
     * @param  Illuminate\Http\Request request;
     * @return \Illuminte\View\Factory view
     */
    public function index(Request $request)
    {
        $notes = Note::orderBy('id', 'desc')
            ->with('place', 'media', 'client')
            ->withCount(['webmentions As replies' => function ($query) {
                $query->where('type', 'in-reply-to');
            }])->paginate(10);

        $homepage = ($request->path() == '/');

        return view('notes.index', compact('notes', 'homepage'));
    }

    /**
     * Show a single note.
     *
     * @param  string The id of the note
     * @return \Illuminate\View\Factory view
     */
    public function show($urlId)
    {
        $authorship = new Authorship();
        $note = Note::nb60($urlId)->first();
        $replies = [];
        $reposts = [];
        $likes = [];
        $carbon = new \Carbon\Carbon();
        foreach ($note->webmentions as $webmention) {
            /*
                reply->url      |
                reply->photo    |   Author
                reply->name     |
                reply->source
                reply->date
                reply->reply

                repost->url     |
                repost->photo   |   Author
                repost->name    |
                repost->date
                repost->source

                like->url       |
                like->photo     |   Author
                like->name      |
            */
            $microformats = json_decode($webmention->mf2, true);
            $authorHCard = $authorship->findAuthor($microformats);
            $content['url'] = $authorHCard['properties']['url'][0];
            $content['photo'] = $this->createPhotoLink($authorHCard['properties']['photo'][0]);
            $content['name'] = $authorHCard['properties']['name'][0];
            switch ($webmention->type) {
                case 'in-reply-to':
                    $content['source'] = $webmention->source;
                    if (isset($microformats['items'][0]['properties']['published'][0])) {
                        try {
                            $content['date'] = $carbon->parse(
                                $microformats['items'][0]['properties']['published'][0]
                            )->toDayDateTimeString();
                        } catch (\Exception $exception) {
                            $content['date'] = $webmention->updated_at->toDayDateTimeString();
                        }
                    } else {
                        $content['date'] = $webmention->updated_at->toDayDateTimeString();
                    }
                    $content['reply'] = $this->filterHTML(
                        $microformats['items'][0]['properties']['content'][0]['html']
                    );
                    $replies[] = $content;
                    break;

                case 'repost-of':
                    $content['date'] = $carbon->parse(
                        $microformats['items'][0]['properties']['published'][0]
                    )->toDayDateTimeString();
                    $content['source'] = $webmention->source;
                    $reposts[] = $content;
                    break;

                case 'like-of':
                    $likes[] = $content;
                    break;
            }
        }

        return view('notes.show', compact('note', 'replies', 'reposts', 'likes'));
    }

    /**
     * Redirect /note/{decID} to /notes/{nb60id}.
     *
     * @param  string The decimal id of he note
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function redirect($decId)
    {
        $numbers = new Numbers();
        $realId = $numbers->numto60($decId);

        $url = config('app.url') . '/notes/' . $realId;

        return redirect($url);
    }

    /**
     * Show all notes tagged with {tag}.
     *
     * @param  string The tag
     * @return \Illuminate\View\Factory view
     */
    public function tagged($tag)
    {
        $notes = Note::whereHas('tags', function ($query) use ($tag) {
            $query->where('tag', $tag);
        })->get();
        foreach ($notes as $note) {
            $note->iso8601_time = $note->updated_at->toISO8601String();
            $note->human_time = $note->updated_at->diffForHumans();
        }

        return view('notes.tagged', compact('notes', 'tag'));
    }

    /**
     * Create the photo link.
     *
     * We shall leave twitter.com and twimg.com links as they are. Then we shall
     * check for local copies, if that fails leave the link as is.
     *
     * @param  string
     * @return string
     */
    public function createPhotoLink($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host == 'pbs.twimg.com') {
            //make sure we use HTTPS, we know twitter supports it
            return str_replace('http://', 'https://', $url);
        }
        if ($host == 'twitter.com') {
            if (Cache::has($url)) {
                return Cache::get($url);
            }
            $username = parse_url($url, PHP_URL_PATH);
            try {
                $info = Twitter::getUsers(['screen_name' => $username]);
                $profile_image = $info->profile_image_url_https;
                Cache::put($url, $profile_image, 10080); //1 week
            } catch (Exception $e) {
                return $url; //not sure here
            }

            return $profile_image;
        }
        $filesystem = new Filesystem();
        if ($filesystem->exists(public_path() . '/assets/profile-images/' . $host . '/image')) {
            return '/assets/profile-images/' . $host . '/image';
        }

        return $url;
    }

    /**
     * Filter the HTML in a reply webmention.
     *
     * @param  string  The reply HTML
     * @return string  The filtered HTML
     */
    private function filterHTML($html)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path() . '/HTMLPurifier');
        $config->set('HTML.TargetBlank', true);
        $purifier = new HTMLPurifier($config);

        return $purifier->purify($html);
    }
}
