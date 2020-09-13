<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Bookmark.
 *
 * @property int $id
 * @property string $url
 * @property string|null $name
 * @property string|null $content
 * @property string|null $screenshot
 * @property string|null $archive
 * @property array|null $syndicates
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $longurl
 * @property-read Collection|Tag[] $tags
 * @property-read int|null $tags_count
 * @method static Builder|Bookmark newModelQuery()
 * @method static Builder|Bookmark newQuery()
 * @method static Builder|Bookmark query()
 * @method static Builder|Bookmark whereArchive($value)
 * @method static Builder|Bookmark whereContent($value)
 * @method static Builder|Bookmark whereCreatedAt($value)
 * @method static Builder|Bookmark whereId($value)
 * @method static Builder|Bookmark whereName($value)
 * @method static Builder|Bookmark whereScreenshot($value)
 * @method static Builder|Bookmark whereSyndicates($value)
 * @method static Builder|Bookmark whereUpdatedAt($value)
 * @method static Builder|Bookmark whereUrl($value)
 * @mixin Eloquent
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
