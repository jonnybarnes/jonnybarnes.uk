<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterHtml;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Mf2;

/**
 * App\Models\Like.
 *
 * @property int $id
 * @property string $url
 * @property string|null $author_name
 * @property string|null $author_url
 * @property string|null $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Like newModelQuery()
 * @method static Builder|Like newQuery()
 * @method static Builder|Like query()
 * @method static Builder|Like whereAuthorName($value)
 * @method static Builder|Like whereAuthorUrl($value)
 * @method static Builder|Like whereContent($value)
 * @method static Builder|Like whereCreatedAt($value)
 * @method static Builder|Like whereId($value)
 * @method static Builder|Like whereUpdatedAt($value)
 * @method static Builder|Like whereUrl($value)
 * @mixin Eloquent
 */
class Like extends Model
{
    use FilterHtml;
    use HasFactory;

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
