<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    /**
     * We shall set a blacklist of non-modifiable model attributes.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Define the relationship with notes.
     *
     * @return BelongsToMany
     */
    public function notes()
    {
        return $this->belongsToMany(Note::class);
    }

    /**
     * The bookmarks that belong to the tag.
     *
     * @return BelongsToMany
     */
    public function bookmarks()
    {
        return $this->belongsToMany('App\Models\Bookmark');
    }

    protected function tag(): Attribute
    {
        return Attribute::set(
            set: fn ($value) => self::normalize($value),
        );
    }

    /**
     * This method actually normalizes a tag. That means lowercase-ing and
     * removing fancy diatric characters.
     */
    public static function normalize(string $tag): string
    {
        return Str::slug($tag);
    }
}
