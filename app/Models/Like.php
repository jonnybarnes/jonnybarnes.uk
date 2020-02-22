<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Mf2;

/**
 * App\Models\Like.
 *
 * @property int $id
 * @property string $url
 * @property string|null $author_name
 * @property string|null $author_url
 * @property string|null $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Like newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Like newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Like query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Like whereAuthorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Like whereAuthorUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Like whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Like whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Like whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Like whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Like whereUrl($value)
 * @mixin \Eloquent
 */
class Like extends Model
{
    use FilterHtml;

    protected $fillable = ['url'];

    /**
     * Normalize the URL of a Like.
     *
     * @param  string  $value The provided URL
     */
    public function setUrlAttribute(string $value)
    {
        $this->attributes['url'] = normalize_url($value);
    }

    /**
     * Normalize the URL of the author of the like.
     *
     * @param string|null $value The author’s url
     */
    public function setAuthorUrlAttribute(?string $value)
    {
        $this->attributes['author_url'] = normalize_url($value);
    }

    /**
     * If the content contains HTML, filter it.
     *
     * @param string|null $value The content of the like
     * @return string|null
     */
    public function getContentAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $mf2 = Mf2\parse($value, $this->url);

        if (Arr::get($mf2, 'items.0.properties.content.0.html')) {
            return $this->filterHtml(
                $mf2['items'][0]['properties']['content'][0]['html']
            );
        }

        return $value;
    }
}
