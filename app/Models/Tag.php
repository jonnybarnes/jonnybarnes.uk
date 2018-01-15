<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    /**
     * We shall set a blacklist of non-modifiable model attributes.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Define the relationship with notes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function notes()
    {
        return $this->belongsToMany('App\Models\Note');
    }

    /**
     * The bookmarks that belong to the tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function bookmarks()
    {
        return $this->belongsToMany('App\Models\Bookmark');
    }

    /**
     * When creating a Tag model instance, invoke the nomralize method on the tag.
     *
     * @param  string  $value
     */
    public function setTagAttribute(string $value)
    {
        $this->attributes['tag'] = $this->normalize($value);
    }

    /**
     * This method actually normalizes a tag. That means lowercase-ing and
     * removing fancy diatric characters.
     *
     * @param  string  $tag
     * @return string
     */
    public static function normalize(string $tag): string
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
