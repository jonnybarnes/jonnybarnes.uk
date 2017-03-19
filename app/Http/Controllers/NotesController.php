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
        $notes = Note::orderBy('id', 'desc')->with('webmentions', 'place', 'media')->paginate(10);
        foreach ($notes as $note) {
            $replies = 0;
            foreach ($note->webmentions as $webmention) {
                if ($webmention->type == 'in-reply-to') {
                    $replies++;
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
                $note->address = $this->reverseGeoCode((float) trim($latlng[0]), (float) trim($latlng[1]));
            }
            if ($note->place !== null) {
                $lnglat = explode(' ', $note->place->location);
                $note->latitude = $lnglat[1];
                $note->longitude = $lnglat[0];
                $note->address = $note->place->name;
                $note->placeLink = '/places/' . $note->place->slug;
                $note->geoJson = $this->getGeoJson(
                    $note->longitude,
                    $note->latitude,
                    $note->place->name,
                    $note->place->icon
                );
            }
            /*$mediaLinks = [];
            foreach ($note->media()->get() as $media) {
                $mediaLinks[] = $media->url;
            }*/
        }

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
        $numbers = new Numbers();
        $authorship = new Authorship();
        $realId = $numbers->b60tonum($urlId);
        $note = Note::find($realId);
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
                        $content['date'] = $carbon->parse(
                            $microformats['items'][0]['properties']['published'][0]
                        )->toDayDateTimeString();
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
        $note->twitter = $this->checkTwitterReply($note->in_reply_to);
        $note->iso8601_time = $note->updated_at->toISO8601String();
        $note->human_time = $note->updated_at->diffForHumans();
        if ($note->location && ($note->place === null)) {
            $pieces = explode(':', $note->location);
            $latlng = explode(',', $pieces[0]);
            $note->latitude = trim($latlng[0]);
            $note->longitude = trim($latlng[1]);
            $note->address = $this->reverseGeoCode((float) trim($latlng[0]), (float) trim($latlng[1]));
        }
        if ($note->place !== null) {
            $lnglat = explode(' ', $note->place->location);
            $note->latitude = $lnglat[1];
            $note->longitude = $lnglat[0];
            $note->address = $note->place->name;
            $note->placeLink = '/places/' . $note->place->slug;
            $note->geoJson = $this->getGeoJson(
                $note->longitude,
                $note->latitude,
                $note->place->name,
                $note->place->icon
            );
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

    /**
     * Do a reverse geocode lookup of a `lat,lng` value.
     *
     * @param  float  The latitude
     * @param  float  The longitude
     * @return string The location HTML
     */
    public function reverseGeoCode(float $latitude, float $longitude): string
    {
        $latlng = $latitude . ',' . $longitude;

        return Cache::get($latlng, function () use ($latlng, $latitude, $longitude) {
            $guzzle = new Client();
            $response = $guzzle->request('GET', 'https://nominatim.openstreetmap.org/reverse', [
                'query' => [
                    'format' => 'json',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ],
                'headers' => ['User-Agent' => 'jonnybarnes.uk via Guzzle, email jonny@jonnybarnes.uk'],
            ]);
            $json = json_decode($response->getBody());
            if (isset($json->address->town)) {
                $address = '<span class="p-locality">'
                    . $json->address->town
                    . '</span>, <span class="p-country-name">'
                    . $json->address->country
                    . '</span>';
                Cache::forever($latlng, $address);

                return $address;
            }
            if (isset($json->address->city)) {
                $address = $json->address->city . ', ' . $json->address->country;
                Cache::forever($latlng, $address);

                return $address;
            }
            if (isset($json->address->county)) {
                $address = '<span class="p-region">'
                    . $json->address->county
                    . '</span>, <span class="p-country-name">'
                    . $json->address->country
                    . '</span>';
                Cache::forever($latlng, $address);

                return $address;
            }
            $adress = '<span class="p-country-name">' . $json->address->country . '</span>';
            Cache::forever($latlng, $address);

            return $address;
        });
    }

    private function getGeoJson($longitude, $latitude, $title, $icon)
    {
        $icon = $icon ?? 'marker';

        return '{
            "type": "Feature",
            "geometry": {
                "type": "Point",
                "coordinates": [' . $longitude . ', ' . $latitude . ']
            },
            "properties": {
                "title": "' . $title . '",
                "icon": "' . $icon . '"
            }
        }';
    }
}
