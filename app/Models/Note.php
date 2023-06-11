<?php

declare(strict_types=1);

namespace App\Models;

use App\CommonMark\Generators\ContactMentionGenerator;
use App\CommonMark\Renderers\ContactMentionRenderer;
use Codebird\Codebird;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Jonnybarnes\IndieWeb\Numbers;
use Laravel\Scout\Searchable;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\Mention\Mention;
use League\CommonMark\Extension\Mention\MentionExtension;
use League\CommonMark\MarkdownConverter;
use Normalizer;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;

class Note extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    /**
     * The regex for matching lone usernames.
     *
     * @var string
     */
    private const USERNAMES_REGEX = '/\[.*?\](*SKIP)(*F)|@(\w+)/';

    /**
     * This variable is used to keep track of contacts in a note.
     */
    protected ?array $contacts;

    /**
     * Set our contacts variable to null.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->contacts = null;
    }

    /** @var string */
    protected $table = 'notes';

    /** @var array<int, string> */
    protected $fillable = [
        'note',
        'in_reply_to',
        'client_id',
    ];

    /** @var array<int, string> */
    protected $hidden = ['searchable'];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(MicropubClient::class, 'client_id', 'client_url');
    }

    public function webmentions(): MorphMany
    {
        return $this->morphMany(WebMention::class, 'commentable');
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'note' => $this->note,
        ];
    }

    public function setNoteAttribute(?string $value): void
    {
        if ($value !== null) {
            $normalized = normalizer_normalize($value, Normalizer::FORM_C);
            if ($normalized === '') { //we don’t want to save empty strings to the db
                $normalized = null;
            }
            $this->attributes['note'] = $normalized;
        }
    }

    /**
     * Pre-process notes for web-view.
     */
    public function getNoteAttribute(?string $value): ?string
    {
        if ($value === null && $this->place !== null) {
            $value = '📍: <a href="' . $this->place->longurl . '">' . $this->place->name . '</a>';
        }

        // if $value is still null, just return null
        if ($value === null) {
            return null;
        }

        $hashtags = $this->autoLinkHashtag($value);

        return $this->convertMarkdown($hashtags);
    }

    /**
     * Provide the content_html for JSON feed.
     *
     * In particular, we want to include media links such as images.
     */
    public function getContentAttribute(): string
    {
        $note = $this->note;

        foreach ($this->media as $media) {
            if ($media->type === 'image') {
                $note .= '<img src="' . $media->url . '" alt="">';
            }
            if ($media->type === 'audio') {
                $note .= '<audio src="' . $media->url . '">';
            }
            if ($media->type === 'video') {
                $note .= '<video src="' . $media->url . '">';
            }
        }

        if ($note === null) {
            // when would $note still be blank?
            $note = 'A blank note';
        }

        return $note;
    }

    public function getNb60idAttribute(): string
    {
        // we cast to string because sometimes the nb60id is an “int”
        return (string) resolve(Numbers::class)->numto60($this->id);
    }

    public function getLongurlAttribute(): string
    {
        return config('app.url') . '/notes/' . $this->nb60id;
    }

    public function getShorturlAttribute(): string
    {
        return config('url.shorturl') . '/notes/' . $this->nb60id;
    }

    public function getIso8601Attribute(): string
    {
        return $this->updated_at->toISO8601String();
    }

    public function getHumandiffAttribute(): string
    {
        return $this->updated_at->diffForHumans();
    }

    public function getPubdateAttribute(): string
    {
        return $this->updated_at->toRSSString();
    }

    public function getLatitudeAttribute(): ?float
    {
        if ($this->place !== null) {
            return $this->place->latitude;
        }
        if ($this->location !== null) {
            $pieces = explode(':', $this->location);
            $latLng = explode(',', $pieces[0]);

            return (float) trim($latLng[0]);
        }

        return null;
    }

    public function getLongitudeAttribute(): ?float
    {
        if ($this->place !== null) {
            return $this->place->longitude;
        }
        if ($this->location !== null) {
            $pieces = explode(':', $this->location);
            $latLng = explode(',', $pieces[0]);

            return (float) trim($latLng[1]);
        }

        return null;
    }

    /**
     * Get the address for a note.
     *
     * This is either a reverse geo-code from the location, or is derived from the associated place.
     */
    public function getAddressAttribute(): ?string
    {
        if ($this->place !== null) {
            return $this->place->name;
        }
        if ($this->location !== null) {
            return $this->reverseGeoCode((float) $this->latitude, (float) $this->longitude);
        }

        return null;
    }

    /**
     * Get the OEmbed html for a tweet the note is a reply to.
     */
    public function getTwitterAttribute(): ?object
    {
        if (
            $this->in_reply_to === null ||
            ! $this->isTwitterLink($this->in_reply_to)
        ) {
            return null;
        }

        $tweetId = basename($this->in_reply_to);
        if (Cache::has($tweetId)) {
            return Cache::get($tweetId);
        }

        try {
            $codebird = resolve(Codebird::class);
            $oEmbed = $codebird->statuses_oembed([
                'url' => $this->in_reply_to,
                'dnt' => true,
                'align' => 'center',
                'maxwidth' => 512,
            ]);

            if ($oEmbed->httpstatus >= 400) {
                throw new Exception();
            }
        } catch (Exception $e) {
            return null;
        }
        Cache::put($tweetId, $oEmbed, ($oEmbed->cache_age));

        return $oEmbed;
    }

    /**
     * Scope a query to select a note via a NewBase60 id.
     */
    public function scopeNb60(Builder $query, string $nb60id): Builder
    {
        $realId = resolve(Numbers::class)->b60tonum($nb60id);

        // Check nb60 does not translate to ID too big for database int4 column
        if ($realId > 2_147_483_647) {
            abort(404);
        }

        return $query->where('id', resolve(Numbers::class)->b60tonum($nb60id));
    }

    /**
     * Swap contact’s nicks for a full mf2 h-card.
     *
     * Take note that this method does two things, given @username (NOT [@username](URL)!)
     * we try to create a fancy hcard from our contact info. If this is not possible
     * due to lack of contact info, we assume @username is a twitter handle and link it
     * as such.
     */
    private function makeHCards(string $text): string
    {
        $this->getContacts();

        if (count($this->contacts) === 0) {
            return $text;
        }

        return preg_replace_callback(
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
    }

    /**
     * Get the value of the `contacts` property.
     */
    public function getContacts(): array
    {
        if ($this->contacts === null) {
            $this->setContacts();
        }

        return $this->contacts;
    }

    /**
     * Process the note and save the contacts to the `contacts` property.
     */
    public function setContacts(): void
    {
        $contacts = [];
        if ($this->getRawOriginal('note')) {
            preg_match_all(self::USERNAMES_REGEX, $this->getRawOriginal('note'), $matches);

            foreach ($matches[1] as $match) {
                $contacts[$match] = Contact::where('nick', mb_strtolower($match))->first();
            }
        }

        $this->contacts = $contacts;
    }

    /**
     * Turn text hashtags to full HTML links.
     *
     * Given a string and section, finds all hashtags matching
     * `#[\-_a-zA-Z0-9]+` and wraps them in an `a` element with
     * `rel=tag` set and a `href` of 'section/tagged/' + tagname without the #.
     */
    public function autoLinkHashtag(string $note): string
    {
        return preg_replace_callback(
            '/#([^\s[:punct:]]+)/',
            function ($matches) {
                return '<a rel="tag" class="p-category" href="/notes/tagged/'
                . Tag::normalize($matches[1]) . '">#'
                . $matches[1] . '</a>';
            },
            $note
        );
    }

    private function convertMarkdown(string $note): string
    {
        $config = [
            'mentions' => [
                'contacts_handle' => [
                    'prefix' => '@',
                    'pattern' => '[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)',
                    'generator' => new ContactMentionGenerator(),
                ],
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new MentionExtension());
        $environment->addRenderer(Mention::class, new ContactMentionRenderer());
        $environment->addRenderer(FencedCode::class, new FencedCodeRenderer());
        $environment->addRenderer(IndentedCode::class, new IndentedCodeRenderer());
        $markdownConverter = new MarkdownConverter($environment);

        return $markdownConverter->convert($note)->getContent();
    }

    public function reverseGeoCode(float $latitude, float $longitude): string
    {
        $latLng = $latitude . ',' . $longitude;

        return Cache::get($latLng, function () use ($latLng, $latitude, $longitude) {
            $guzzle = resolve(Client::class);
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
            $json = json_decode((string) $response->getBody());
            if (isset($json->address->suburb)) {
                $locality = $json->address->suburb;
                if (isset($json->address->city)) {
                    $locality .= ', ' . $json->address->city;
                }
                $address = '<span class="p-locality">'
                    . $locality
                    . '</span>, <span class="p-country-name">'
                    . $json->address->country
                    . '</span>';
                Cache::forever($latLng, $address);

                return $address;
            }
            if (isset($json->address->city)) {
                $address = '<span class="p-locality">'
                    . $json->address->city
                    . '</span>, <span class="p-country-name">'
                    . $json->address->country
                    . '</span>';
                Cache::forever($latLng, $address);

                return $address;
            }
            if (isset($json->address->county)) {
                $address = '<span class="p-region">'
                    . $json->address->county
                    . '</span>, <span class="p-country-name">'
                    . $json->address->country
                    . '</span>';
                Cache::forever($latLng, $address);

                return $address;
            }
            $address = '<span class="p-country-name">' . $json->address->country . '</span>';
            Cache::forever($latLng, $address);

            return $address;
        });
    }

    private function isTwitterLink(string $inReplyTo): bool
    {
        return str_starts_with($inReplyTo, 'https://twitter.com/');
    }
}
