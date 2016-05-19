<?php

namespace App;

use Normalizer;
use Jonnybarnes\IndieWeb\Numbers;
use Illuminate\Database\Eloquent\Model;
use Jonnybarnes\UnicodeTools\UnicodeTools;
use League\CommonMark\CommonMarkConverter;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMedia;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Note extends Model implements HasMedia
{
    use SoftDeletes;
    use HasMediaTrait;

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
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

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
        $unicode = new UnicodeTools();
        $codepoints = $unicode->convertUnicodeCodepoints($value);
        $markdown = new CommonMarkConverter();
        $html = $markdown->convertToHtml($codepoints);
        $hcards = $this->makeHCards($html);
        $hashtags = $this->autoLinkHashtag($hcards);

        return $hashtags;
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
        $name = Client::where('client_url', $this->client_id)->value('client_name');
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
                    return '<a href="https://twitter.com/' . $matches[1] . '">' . $matches[0] . '</a>';
                }
                $path = parse_url($contact->homepage)['host'];
                $contact->photo = (file_exists(public_path() . '/assets/profile-images/' . $path . '/image')) ?
                    '/assets/profile-images/' . $path . '/image'
                :
                    '/assets/profile-images/default-image';

                return trim(view('mini-hcard-template', ['contact' => $contact])->render());
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
                  '<a rel="tag" class="p-category" href="/notes/tagged/' . $name . '">#' . $name . '</a>';
            }

            // Replace #tags with valid microformat-enabled link
            foreach ($replacements as $name => $replacement) {
                $text = str_replace('#' . $name, $replacement, $text);
            }
        }

        return $text;
    }
}
