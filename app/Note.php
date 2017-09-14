<?php

namespace App;

use Cache;
use Twitter;
use Normalizer;
use GuzzleHttp\Client;
use Laravel\Scout\Searchable;
use League\CommonMark\Converter;
use League\CommonMark\DocParser;
use Jonnybarnes\IndieWeb\Numbers;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use Illuminate\Database\Eloquent\Model;
use Jonnybarnes\EmojiA11y\EmojiModifier;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jonnybarnes\CommonmarkLinkify\LinkifyExtension;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Note extends Model
{
    use Searchable;
    use SoftDeletes;

    /**
     * The reges for matching lone usernames.
     *
     * @var string
     */
    private const USERNAMES_REGEX = '/\[.*?\](*SKIP)(*F)|@(\w+)/';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'notes';

    /**
     * Define the relationship with tags.
     *
     * @var array
     */
    public function tags()
    {
        return $this->belongsToMany('App\Tag');
    }

    /**
     * Define the relationship with clients.
     *
     * @var array?
     */
    public function client()
    {
        return $this->belongsTo('App\MicropubClient', 'client_id', 'client_url');
    }

    /**
     * Define the relationship with webmentions.
     *
     * @var array
     */
    public function webmentions()
    {
        return $this->morphMany('App\WebMention', 'commentable');
    }

    /**
     * Definte the relationship with places.
     *
     * @var array
     */
    public function place()
    {
        return $this->belongsTo('App\Place');
    }

    /**
     * Define the relationship with media.
     *
     * @return void
     */
    public function media()
    {
        return $this->hasMany('App\Media');
    }

    /**
     * We shall set a blacklist of non-modifiable model attributes.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Hide the column used with Laravel Scout.
     *
     * @var array
     */
    protected $hidden = ['searchable'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Set the attributes to be indexed for searching with Scout.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'note' => $this->note,
        ];
    }

    /**
     * Normalize the note to Unicode FORM C.
     *
     * @param  string  $value
     * @return string
     */
    public function setNoteAttribute($value)
    {
        $this->attributes['note'] = normalizer_normalize($value, Normalizer::FORM_C);
    }

    /**
     * Pre-process notes for web-view.
     *
     * @param  string
     * @return string
     */
    public function getNoteAttribute($value)
    {
        $emoji = new EmojiModifier();

        $html = $this->convertMarkdown($value);
        $hcards = $this->makeHCards($html);
        $hashtags = $this->autoLinkHashtag($hcards);
        $modified = $emoji->makeEmojiAccessible($hashtags);

        return $modified;
    }

    /**
     * Generate the NewBase60 ID from primary ID.
     *
     * @return string
     */
    public function getNb60idAttribute()
    {
        $numbers = new Numbers();

        return $numbers->numto60($this->id);
    }

    /**
     * The Long URL for a note.
     *
     * @return string
     */
    public function getLongurlAttribute()
    {
        return config('app.url') . '/notes/' . $this->nb60id;
    }

    /**
     * The Short URL for a note.
     *
     * @return string
     */
    public function getShorturlAttribute()
    {
        return config('app.shorturl') . '/notes/' . $this->nb60id;
    }

    /**
     * Get the ISO8601 value for mf2.
     *
     * @return string
     */
    public function getIso8601Attribute()
    {
        return $this->updated_at->toISO8601String();
    }

    /**
     * Get the ISO8601 value for mf2.
     *
     * @return string
     */
    public function getHumandiffAttribute()
    {
        return $this->updated_at->diffForHumans();
    }

    /**
     * Get the pubdate value for RSS feeds.
     *
     * @return string
     */
    public function getPubdateAttribute()
    {
        return $this->updated_at->toRSSString();
    }

    /**
     * Get the latitude value.
     *
     * @return string|null
     */
    public function getLatitudeAttribute()
    {
        if ($this->place !== null) {
            $lnglat = explode(' ', $this->place->location);

            return $lnglat[1];
        }
        if ($this->location !== null) {
            $pieces = explode(':', $this->location);
            $latlng = explode(',', $pieces[0]);

            return trim($latlng[0]);
        }
    }

    /**
     * Get the longitude value.
     *
     * @return string|null
     */
    public function getLongitudeAttribute()
    {
        if ($this->place !== null) {
            $lnglat = explode(' ', $this->place->location);

            return $lnglat[1];
        }
        if ($this->location !== null) {
            $pieces = explode(':', $this->location);
            $latlng = explode(',', $pieces[0]);

            return trim($latlng[1]);
        }
    }

    /**
     * Get the address for a note. This is either a reverse geo-code from the
     * location, or is derived from the associated place.
     *
     * @return string|null
     */
    public function getAddressAttribute()
    {
        if ($this->place !== null) {
            return $this->place->name;
        }
        if ($this->location !== null) {
            return $this->reverseGeoCode((float) $this->latitude, (float) $this->longitude);
        }
    }

    public function getTwitterAttribute()
    {
        if ($this->in_reply_to == null || mb_substr($this->in_reply_to, 0, 20, 'UTF-8') !== 'https://twitter.com/') {
            return;
        }

        $tweetId = basename($this->in_reply_to);
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
     * Show a specific form of the note for twitter.
     */
    public function getTwitterContentAttribute()
    {
        // find @mentions
        preg_match_all(self::USERNAMES_REGEX, $this->getOriginal('note'), $matches);
        if (count($matches[1]) === 0) {
            return;
        }

        // check if any @mentions have a contact associated with them
        $count = 0;
        foreach ($matches[1] as $match) {
            $contact = Contact::where('nick', '=', mb_strtolower($match))->first();
            if ($contact) {
                $count++;
            }
        }
        if ($count === 0) {
            return;
        }

        // swap in twitter usernames
        $swapped = preg_replace_callback(
            self::USERNAMES_REGEX,
            function ($matches) {
                try {
                    $contact = Contact::where('nick', '=', mb_strtolower($matches[1]))->firstOrFail();
                } catch (ModelNotFoundException $e) {
                    //assume its an actual twitter handle
                    return $matches[0];
                }
                if ($contact->twitter) {
                    return '@' . $contact->twitter;
                }

                return $contact->name;
            },
            $this->getOriginal('note')
        );

        return $this->convertMarkdown($swapped);
    }

    public function getFacebookContentAttribute()
    {
        // find @mentions
        preg_match_all(self::USERNAMES_REGEX, $this->getOriginal('note'), $matches);
        if (count($matches[1]) === 0) {
            return;
        }

        // check if any @mentions have a contact associated with them
        $count = 0;
        foreach ($matches[1] as $match) {
            $contact = Contact::where('nick', '=', mb_strtolower($match))->first();
            if ($contact) {
                $count++;
            }
        }
        if ($count === 0) {
            return;
        }

        // swap in facebook usernames
        $swapped = preg_replace_callback(
            self::USERNAMES_REGEX,
            function ($matches) {
                try {
                    $contact = Contact::where('nick', '=', mb_strtolower($matches[1]))->firstOrFail();
                } catch (ModelNotFoundException $e) {
                    //assume its an actual twitter handle
                    return $matches[0];
                }
                if ($contact->facebook) {
                    return '<a class="u-category h-card" href="https://facebook.com/'
                           . $contact->facebook . '">' . $contact->name . '</a>';
                }

                return $contact->name;
            },
            $this->getOriginal('note')
        );

        return $this->convertMarkdown($swapped);
    }

    /**
     * Scope a query to select a note via a NewBase60 id.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $nb60id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNb60($query, $nb60id)
    {
        $numbers = new Numbers();

        return $query->where('id', $numbers->b60tonum($nb60id));
    }

    /**
     * Take note that this method does two things, given @username (NOT [@username](URL)!)
     * we try to create a fancy hcard from our contact info. If this is not possible
     * due to lack of contact info, we assume @username is a twitter handle and link it
     * as such.
     *
     * @param  string  The note’s text
     * @return string
     */
    private function makeHCards($text)
    {
        $hcards = preg_replace_callback(
            self::USERNAMES_REGEX,
            function ($matches) {
                try {
                    $contact = Contact::where('nick', '=', mb_strtolower($matches[1]))->firstOrFail();
                } catch (ModelNotFoundException $e) {
                    //assume its an actual twitter handle
                    return '<a href="https://twitter.com/' . $matches[1] . '">' . $matches[0] . '</a>';
                }
                $host = parse_url($contact->homepage, PHP_URL_HOST);
                $contact->photo = (file_exists(public_path() . '/assets/profile-images/' . $host . '/image')) ?
                    '/assets/profile-images/' . $host . '/image'
                :
                    '/assets/profile-images/default-image';

                return trim(view('templates.mini-hcard', ['contact' => $contact])->render());
            },
            $text
        );

        return $hcards;
    }

    /**
     * Given a string and section, finds all hashtags matching
     * `#[\-_a-zA-Z0-9]+` and wraps them in an `a` element with
     * `rel=tag` set and a `href` of 'section/tagged/' + tagname without the #.
     *
     * @param  string  The note
     * @return string
     */
    private function autoLinkHashtag($text)
    {
        // $replacements = ["#tag" => "<a rel="tag" href="/tags/tag">#tag</a>]
        $replacements = [];
        $matches = [];

        if (preg_match_all('/(?<=^|\s)\#([a-zA-Z0-9\-\_]+)/i', $text, $matches, PREG_PATTERN_ORDER)) {
            // Look up #tags, get Full name and URL
            foreach ($matches[0] as $name) {
                $name = str_replace('#', '', $name);
                $replacements[$name] =
                  '<a rel="tag" class="p-category" href="/notes/tagged/'
                    . Tag::normalizeTag($name)
                    . '">#'
                    . $name
                    . '</a>';
            }

            // Replace #tags with valid microformat-enabled link
            foreach ($replacements as $name => $replacement) {
                $text = str_replace('#' . $name, $replacement, $text);
            }
        }

        return $text;
    }

    private function convertMarkdown($text)
    {
        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension(new LinkifyExtension());
        $converter = new Converter(new DocParser($environment), new HtmlRenderer($environment));

        return $converter->convertToHtml($text);
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
}
