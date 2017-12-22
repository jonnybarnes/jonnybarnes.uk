<?php

namespace App\Models;

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

    protected $contacts;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->contacts = null;
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'notes';

    /*
     * Mass-assignment
     *
     * @var array
     */
    protected $fillable = [
        'note',
        'in_reply_to',
        'client_id',
    ];

    /**
     * Hide the column used with Laravel Scout.
     *
     * @var array
     */
    protected $hidden = ['searchable'];

    /**
     * Define the relationship with tags.
     *
     * @var array
     */
    public function tags()
    {
        return $this->belongsToMany('App\Models\Tag');
    }

    /**
     * Define the relationship with clients.
     *
     * @var array?
     */
    public function client()
    {
        return $this->belongsTo('App\Models\MicropubClient', 'client_id', 'client_url');
    }

    /**
     * Define the relationship with webmentions.
     *
     * @var array
     */
    public function webmentions()
    {
        return $this->morphMany('App\Models\WebMention', 'commentable');
    }

    /**
     * Definte the relationship with places.
     *
     * @var array
     */
    public function place()
    {
        return $this->belongsTo('App\Models\Place');
    }

    /**
     * Define the relationship with media.
     *
     * @return void
     */
    public function media()
    {
        return $this->hasMany('App\Models\Media');
    }

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

        $hcards = $this->makeHCards($value);
        $hashtags = $this->autoLinkHashtag($hcards);
        $html = $this->convertMarkdown($hashtags);
        $modified = $emoji->makeEmojiAccessible($html);

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
            return $this->place->location->getLat();
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
            return $this->place->location->getLng();
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
                'url' => $this->in_reply_to,
                'dnt' => true,
                'align' => 'center',
                'maxwidth' => 512,
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
        if ($this->contacts === null) {
            return;
        }

        if (count($this->contacts) === 0) {
            return;
        }

        if (count(array_unique(array_values($this->contacts))) === 1
            && array_unique(array_values($this->contacts))[0] === null) {
            return;
        }

        // swap in twitter usernames
        $swapped = preg_replace_callback(
            self::USERNAMES_REGEX,
            function ($matches) {
                if (is_null($this->contacts[$matches[1]])) {
                    return $matches[0];
                }

                $contact = $this->contacts[$matches[1]];
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
        if (count($this->contacts) === 0) {
            return;
        }

        if (count(array_unique(array_values($this->contacts))) === 1
            && array_unique(array_values($this->contacts))[0] === null) {
            return;
        }

        // swap in facebook usernames
        $swapped = preg_replace_callback(
            self::USERNAMES_REGEX,
            function ($matches) {
                if (is_null($this->contacts[$matches[1]])) {
                    return $matches[0];
                }

                $contact = $this->contacts[$matches[1]];
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
        $this->getContacts();

        if (count($this->contacts) === 0) {
            return $text;
        }

        $hcards = preg_replace_callback(
            self::USERNAMES_REGEX,
            function ($matches) {
                if (is_null($this->contacts[$matches[1]])) {
                    return '<a href="https://twitter.com/' . $matches[1] . '">' . $matches[0] . '</a>';
                }

                $contact = $this->contacts[$matches[1]]; // easier to read the following code
                $host = parse_url($contact->homepage, PHP_URL_HOST);
                $contact->photo = '/assets/profile-images/default-image';
                if (file_exists(public_path() . '/assets/profile-images/' . $host . '/image')) {
                    $contact->photo = '/assets/profile-images/' . $host . '/image';
                }

                return trim(view('templates.mini-hcard', ['contact' => $contact])->render());
            },
            $text
        );

        return $hcards;
    }

    public function getContacts()
    {
        if ($this->contacts === null) {
            $this->setContacts();
        }
    }

    public function setContacts()
    {
        $contacts = [];
        if ($this->getOriginal('note')) {
            preg_match_all(self::USERNAMES_REGEX, $this->getoriginal('note'), $matches);

            foreach ($matches[1] as $match) {
                $contacts[$match] = Contact::where('nick', mb_strtolower($match))->first();
            }
        }

        $this->contacts = $contacts;
    }

    /**
     * Given a string and section, finds all hashtags matching
     * `#[\-_a-zA-Z0-9]+` and wraps them in an `a` element with
     * `rel=tag` set and a `href` of 'section/tagged/' + tagname without the #.
     *
     * @param  string  The note
     * @return string
     */
    public function autoLinkHashtag($text)
    {
        return preg_replace_callback(
            '/#([^\s]*)\b/',
            function ($matches) {
                return '<a rel="tag" class="p-category" href="/notes/tagged/'
                . Tag::normalize($matches[1]) . '">#'
                . Tag::normalize($matches[1]) . '</a>';
            },
            $text
        );
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
            $address = '<span class="p-country-name">' . $json->address->country . '</span>';
            Cache::forever($latlng, $address);

            return $address;
        });
    }
}
