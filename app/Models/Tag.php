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

    /** @var array<int, string> */
    protected $guarded = ['id'];

    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class);
    }

    public function bookmarks(): BelongsToMany
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
     * Normalizes a tag.
     *
     * That means convert to lowercase and removing fancy diatric characters.
     */
    public static function normalize(string $tag): string
    {
        return Str::slug($tag);
    }
}
