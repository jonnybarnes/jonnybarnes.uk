<?php

declare(strict_types=1);

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Element\IndentedCode;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;

/**
 * App\Models\Article.
 *
 * @property int $id
 * @property string $titleurl
 * @property string|null $url
 * @property string $title
 * @property string $main
 * @property int $published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $html
 * @property-read string $human_time
 * @property-read string $link
 * @property-read string $pubdate
 * @property-read string $tooltip_time
 * @property-read string $w3c_time
 * @method static Builder|Article date($year = null, $month = null)
 * @method static Builder|Article findSimilarSlugs($attribute, $config, $slug)
 * @method static bool|null forceDelete()
 * @method static Builder|Article newModelQuery()
 * @method static Builder|Article newQuery()
 * @method static \Illuminate\Database\Query\Builder|Article onlyTrashed()
 * @method static Builder|Article query()
 * @method static bool|null restore()
 * @method static Builder|Article whereCreatedAt($value)
 * @method static Builder|Article whereDeletedAt($value)
 * @method static Builder|Article whereId($value)
 * @method static Builder|Article whereMain($value)
 * @method static Builder|Article wherePublished($value)
 * @method static Builder|Article whereTitle($value)
 * @method static Builder|Article whereTitleurl($value)
 * @method static Builder|Article whereUpdatedAt($value)
 * @method static Builder|Article whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|Article withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Article withoutTrashed()
 * @mixin Eloquent
 */
class Article extends Model
{
    use Sluggable;
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'articles';

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'titleurl' => [
                'source' => 'title',
            ],
        ];
    }

    /**
     * We shall set a blacklist of non-modifiable model attributes.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Process the article for display.
     *
     * @return string
     */
    public function getHtmlAttribute(): string
    {
        $environment = Environment::createCommonMarkEnvironment();
        $environment->addBlockRenderer(FencedCode::class, new FencedCodeRenderer());
        $environment->addBlockRenderer(IndentedCode::class, new IndentedCodeRenderer());
        $commonMarkConverter = new CommonMarkConverter([], $environment);

        return $commonMarkConverter->convertToHtml($this->main);
    }

    /**
     * Convert updated_at to W3C time format.
     *
     * @return string
     */
    public function getW3cTimeAttribute(): string
    {
        return $this->updated_at->toW3CString();
    }

    /**
     * Convert updated_at to a tooltip appropriate format.
     *
     * @return string
     */
    public function getTooltipTimeAttribute(): string
    {
        return $this->updated_at->toRFC850String();
    }

    /**
     * Convert updated_at to a human readable format.
     *
     * @return string
     */
    public function getHumanTimeAttribute(): string
    {
        return $this->updated_at->diffForHumans();
    }

    /**
     * Get the pubdate value for RSS feeds.
     *
     * @return string
     */
    public function getPubdateAttribute(): string
    {
        return $this->updated_at->toRSSString();
    }

    /**
     * A link to the article, i.e. `/blog/1999/12/25/merry-christmas`.
     *
     * @return string
     */
    public function getLinkAttribute(): string
    {
        return '/blog/' . $this->updated_at->year . '/' . $this->updated_at->format('m') . '/' . $this->titleurl;
    }

    /**
     * Scope a query to only include articles from a particular year/month.
     *
     * @param Builder $query
     * @param int|null $year
     * @param int|null $month
     * @return Builder
     */
    public function scopeDate(Builder $query, int $year = null, int $month = null): Builder
    {
        if ($year == null) {
            return $query;
        }
        $start = $year . '-01-01 00:00:00';
        $end = ($year + 1) . '-01-01 00:00:00';
        if (($month !== null) && ($month !== 12)) {
            $start = $year . '-' . $month . '-01 00:00:00';
            $end = $year . '-' . ($month + 1) . '-01 00:00:00';
        }
        if ($month === 12) {
            $start = $year . '-12-01 00:00:00';
            $end = ($year + 1) . '-01-01 00:00:00';
        }

        return $query->where([
            ['updated_at', '>=', $start],
            ['updated_at', '<', $end],
        ]);
    }
}
