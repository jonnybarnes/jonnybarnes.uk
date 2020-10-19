<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Tag.
 *
 * @property int $id
 * @property string $tag
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Bookmark[] $bookmarks
 * @property-read int|null $bookmarks_count
 * @property-read Collection|Note[] $notes
 * @property-read int|null $notes_count
 * @method static Builder|Tag newModelQuery()
 * @method static Builder|Tag newQuery()
 * @method static Builder|Tag query()
 * @method static Builder|Tag whereCreatedAt($value)
 * @method static Builder|Tag whereId($value)
 * @method static Builder|Tag whereTag($value)
 * @method static Builder|Tag whereUpdatedAt($value)
 * @mixin Eloquent
 */
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
        return $this->belongsToMany('App\Models\Note');
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

    /**
     * When creating a Tag model instance, invoke the nomralize method on the tag.
     *
     * @param string $value
     */
    public function setTagAttribute(string $value)
    {
        $this->attributes['tag'] = $this->normalize($value);
    }

    /**
     * This method actually normalizes a tag. That means lowercase-ing and
     * removing fancy diatric characters.
     *
     * @param string $tag
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
