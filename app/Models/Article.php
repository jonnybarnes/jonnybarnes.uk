<?php

declare(strict_types=1);

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\MarkdownConverter;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;

class Article extends Model
{
    use HasFactory;
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
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addRenderer(FencedCode::class, new FencedCodeRenderer());
        $environment->addRenderer(IndentedCode::class, new IndentedCodeRenderer());
        $markdownConverter = new MarkdownConverter($environment);

        return $markdownConverter->convert($this->main)->getContent();
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
     * @param  Builder  $query
     * @param  int|null  $year
     * @param  int|null  $month
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
