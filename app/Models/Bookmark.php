<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Bookmark
 *
 * @property int $id
 * @property string $url
 * @property string|null $name
 * @property string|null $content
 * @property string|null $screenshot
 * @property string|null $archive
 * @property array|null $syndicates
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $longurl
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark whereArchive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark whereScreenshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark whereSyndicates($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bookmark whereUrl($value)
 * @mixin \Eloquent
 */
class Bookmark extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['url', 'name', 'content'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'syndicates' => 'array',
    ];

    /**
     * The tags that belong to the bookmark.
     *
     * @return  BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany('App\Models\Tag');
    }

    /**
     * The full url of a bookmark.
     *
     * @return string
     */
    public function getLongurlAttribute(): string
    {
        return config('app.url') . '/bookmarks/' . $this->id;
    }
}
