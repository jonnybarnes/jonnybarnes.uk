<?php

declare(strict_types=1);

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'title',
        'main',
        'published',
    ];

    protected function html(): Attribute
    {
        return Attribute::get(
            get: function () {
                $environment = new Environment();
                $environment->addExtension(new CommonMarkCoreExtension());
                $environment->addRenderer(FencedCode::class, new FencedCodeRenderer());
                $environment->addRenderer(IndentedCode::class, new IndentedCodeRenderer());
                $markdownConverter = new MarkdownConverter($environment);

                return $markdownConverter->convert($this->main)->getContent();
            },
        );
    }

    protected function w3cTime(): Attribute
    {
        return Attribute::get(
            get: fn () => $this->updated_at->toW3CString(),
        );
    }

    protected function tooltipTime(): Attribute
    {
        return Attribute::get(
            get: fn () => $this->updated_at->toRFC850String(),
        );
    }

    protected function humanTime(): Attribute
    {
        return Attribute::get(
            get: fn () => $this->updated_at->diffForHumans(),
        );
    }

    protected function pubdate(): Attribute
    {
        return Attribute::get(
            get: fn () => $this->updated_at->toRSSString(),
        );
    }

    protected function link(): Attribute
    {
        return Attribute::get(
            get: fn () => '/blog/' . $this->updated_at->year . '/' . $this->updated_at->format('m') . '/' . $this->titleurl,
        );
    }

    /**
     * Scope a query to only include articles from a particular year/month.
     */
    public function scopeDate(Builder $query, int $year = null, int $month = null): Builder
    {
        if ($year === null) {
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
