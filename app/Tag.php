<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    /**
     * Define the relationship with tags.
     *
     * @var array
     */
    public function notes()
    {
        return $this->belongsToMany('App\Note');
    }

    /**
     * The bookmarks that belong to the tag.
     */
    public function bookmarks()
    {
        return $this->belongsToMany('App\Bookmark');
    }

    /**
     * We shall set a blacklist of non-modifiable model attributes.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Normalize tags so they’re lowercase and fancy diatrics are removed.
     *
     * @param  string
     */
    public function setTagAttribute($value)
    {
        $this->attributes['tag'] = $this->normalize($value);
    }

    /**
     * This method actually normalizes a tag. That means lowercase-ing and
     * removing fancy diatric characters.
     *
     * @param  string
     */
    public static function normalize($tag)
    {
        return mb_strtolower(
            preg_replace(
                '/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);/i',
                '$1',
                htmlentities($tag)
            ),
            'UTF-8'
        );
    }
}
