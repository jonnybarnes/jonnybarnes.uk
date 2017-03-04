<?php

namespace App;

use Normalizer;
use Laravel\Scout\Searchable;
use Jonnybarnes\IndieWeb\Numbers;
use Illuminate\Database\Eloquent\Model;
use Jonnybarnes\EmojiA11y\EmojiModifier;
use League\CommonMark\CommonMarkConverter;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Note extends Model
{
    use Searchable;
    use SoftDeletes;

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
     * A mutator to ensure that in-reply-to is always non-empty or null.
     *
     * @param  string  value
     * @return string
     */
    public function setInReplyToAttribute($value)
    {
        $this->attributes['in_reply_to'] = empty($value) ? null : $value;
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
        $markdown = new CommonMarkConverter();
        $emoji = new EmojiModifier();
        $html = $markdown->convertToHtml($value);
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
     * Get the relavent client name assocaited with the client id.
     *
     * @return string|null
     */
    public function getClientNameAttribute()
    {
        if ($this->client_id == null) {
            return;
        }
        $name = MicropubClient::where('client_url', $this->client_id)->value('client_name');
        if ($name == null) {
            $url = parse_url($this->client_id);
            if (isset($url['path'])) {
                return $url['host'] . $url['path'];
            }

            return $url['host'];
        }

        return $name;
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
        $regex = '/\[.*?\](*SKIP)(*F)|@(\w+)/'; //match @alice but not [@bob](...)
        $hcards = preg_replace_callback(
            $regex,
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
}
