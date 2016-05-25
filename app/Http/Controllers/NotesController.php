<?php

namespace App\Http\Controllers;

use Cache;
use Twitter;
use App\Tag;
use App\Note;
use Jonnybarnes\IndieWeb\Numbers;

// Need to sort out Twitter and webmentions!

class NotesController extends Controller
{
    /**
     * Show all the notes.
     *
     * @return \Illuminte\View\Factory view
     */
    public function showNotes()
    {
        $notes = Note::orderBy('id', 'desc')->with('webmentions', 'place')->simplePaginate(10);
        foreach ($notes as $note) {
            $replies = 0;
            foreach ($note->webmentions as $webmention) {
                if ($webmention->type == 'reply') {
                    $replies = $replies + 1;
                }
            }
            $note->replies = $replies;
            $note->twitter = $this->checkTwitterReply($note->in_reply_to);
            $note->iso8601_time = $note->updated_at->toISO8601String();
            $note->human_time = $note->updated_at->diffForHumans();
            if ($note->location && ($note->place === null)) {
                $pieces = explode(':', $note->location);
                $latlng = explode(',', $pieces[0]);
                $note->latitude = trim($latlng[0]);
                $note->longitude = trim($latlng[1]);
                if (count($pieces) == 2) {
                    $note->address = $pieces[1];
                }
            }
            if ($note->place !== null) {
                preg_match('/\((.*)\)/', $note->place->location, $matches);
                $lnglat = explode(' ', $matches[1]);
                $note->latitude = $lnglat[1];
                $note->longitude = $lnglat[0];
                $note->address = $note->place->name;
                $note->placeLink = '/places/' . $note->place->slug;
            }
            $photoURLs = [];
            $photos = $note->getMedia();
            foreach ($photos as $photo) {
                $photoURLs[] = $photo->getUrl();
            }
            $note->photoURLs = $photoURLs;
        }

        return view('allnotes', ['notes' => $notes]);
    }

    /**
     * Show a single note.
     *
     * @param  string The id of the note
     * @return \Illuminate\View\Factory view
     */
    public function singleNote($urlId)
    {
        $numbers = new Numbers();
        $realId = $numbers->b60tonum($urlId);
        $note = Note::find($realId);
        $replies = [];
        $reposts = [];
        $likes = [];
        foreach ($note->webmentions as $webmention) {
            switch ($webmention->type) {
                case 'reply':
                    $content = unserialize($webmention->content);
                    $content['source'] = $this->bridgyReply($webmention->source);
                    $content['photo'] = $this->createPhotoLink($content['photo']);
                    $content['date'] = $carbon->parse($content['date'])->toDayDateTimeString();
                    $replies[] = $content;
                    break;

                case 'repost':
                    $content = unserialize($webmention->content);
                    $content['photo'] = $this->createPhotoLink($content['photo']);
                    $content['date'] = $carbon->parse($content['date'])->toDayDateTimeString();
                    $reposts[] = $content;
                    break;

                case 'like':
                    $content = unserialize($webmention->content);
                    $content['photo'] = $this->createPhotoLink($content['photo']);
                    $likes[] = $content;
                    break;
            }
        }
        $note->twitter = $this->checkTwitterReply($note->in_reply_to);
        $note->iso8601_time = $note->updated_at->toISO8601String();
        $note->human_time = $note->updated_at->diffForHumans();
        if ($note->location && ($note->place === null)) {
            $pieces = explode(':', $note->location);
            $latlng = explode(',', $pieces[0]);
            $note->latitude = trim($latlng[0]);
            $note->longitude = trim($latlng[1]);
            if (count($pieces) == 2) {
                $note->address = $pieces[1];
            }
        }
        if ($note->place !== null) {
            preg_match('/\((.*)\)/', $note->place->location, $matches);
            $lnglat = explode(' ', $matches[1]);
            $note->latitude = $lnglat[1];
            $note->longitude = $lnglat[0];
            $note->address = $note->place->name;
            $note->placeLink = '/places/' . $note->place->slug;
        }

        $note->photoURLs = [];
        foreach ($note->getMedia() as $photo) {
            $note->photoURLs[] = $photo->getUrl();
        }

        return view('singlenote', [
            'note' => $note,
            'replies' => $replies,
            'reposts' => $reposts,
            'likes' => $likes,
        ]);
    }

    /**
     * Redirect /note/{decID} to /notes/{nb60id}.
     *
     * @param  string The decimal id of he note
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function singleNoteRedirect($decId)
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
    public function taggedNotes($tag)
    {
        $tagId = Tag::where('tag', $tag)->pluck('id');
        $notes = Tag::find($tagId)->notes()->orderBy('updated_at', 'desc')->get();
        foreach ($notes as $note) {
            $note->iso8601_time = $note->updated_at->toISO8601String();
            $note->human_time = $note->updated_at->diffForHumans();
        }

        return view('taggednotes', ['notes' => $notes, 'tag' => $tag]);
    }

    /**
     * Swap a brid.gy URL shim-ing a twitter reply to a real twitter link.
     *
     * @param  string
     * @return string
     */
    public function bridgyReply($source)
    {
        $url = $source;
        if (mb_substr($source, 0, 28, 'UTF-8') == 'https://brid-gy.appspot.com/') {
            $parts = explode('/', $source);
            $tweetId = array_pop($parts);
            if ($tweetId) {
                $url = 'https://twitter.com/_/status/' . $tweetId;
            }
        }

        return $url;
    }

    /**
     * Create the photo link.
     *
     * @param  string
     * @return string
     */
    public function createPhotoLink($url)
    {
        $host = parse_url($url)['host'];
        if ($host != 'twitter.com' && $host != 'pbs.twimg.com') {
            return '/assets/profile-images/' . $host . '/image';
        }
        if (mb_substr($url, 0, 20) == 'http://pbs.twimg.com') {
            return str_replace('http://', 'https://', $url);
        }
    }

    /**
     * Twitter!!!
     *
     * @param  string  The reply to URL
     * @return string | null
     */
    private function checkTwitterReply($url)
    {
        if ($url == null) {
            return;
        }

        if (mb_substr($url, 0, 20, 'UTF-8') !== 'https://twitter.com/') {
            return;
        }

        $arr = explode('/', $url);
        $tweetId = end($arr);
        if (Cache::has($tweetId)) {
            return Cache::get($tweetId);
        }
        try {
            $oEmbed = Twitter::getOembed([
                'id' => $tweetId,
                'align' => 'center',
                'omit_script' => true,
                'maxwidth' => 550,
            ]);
        } catch (\Exception $e) {
            return;
        }
        Cache::put($tweetId, $oEmbed, ($oEmbed->cache_age / 60));

        return $oEmbed;
    }
}
