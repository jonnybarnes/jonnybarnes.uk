<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Jonnybarnes\UnicodeTools\UnicodeTools;
use League\CommonMark\CommonMarkConverter;
use MartinBean\Database\Eloquent\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    /*
     * We want to turn the titles into slugs
     */
    use Sluggable;
    const DISPLAY_NAME = 'title';
    const SLUG = 'titleurl';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'articles';

    /**
     * Define the relationship with webmentions.
     *
     * @var array
     */
    public function webmentions()
    {
        return $this->morphMany('App\WebMention', 'commentable');
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
    public function getMainAttribute($value)
    {
        $unicode = new UnicodeTools();
        $markdown = new CommonMarkConverter();
        $html = $markdown->convertToHtml($unicode->convertUnicodeCodepoints($value));
        //change <pre><code>[lang] ~> <pre><code data-language="lang">
        $match = '/<pre><code>\[(.*)\]\n/';
        $replace = '<pre><code class="language-$1">';
        $text = preg_replace($match, $replace, $html);
        $default = preg_replace('/<pre><code>/', '<pre><code class="language-markdown">', $text);

        return $default;
    }

    /**
     * Convert updated_at to W3C time format.
     *
     * @return string
     */
    public function getW3cTimeAttribute()
    {
        return $this->updated_at->toW3CString();
    }

    /**
     * Convert updated_at to a tooltip appropriate format.
     *
     * @return string
     */
    public function getTooltipTimeAttribute()
    {
        return $this->updated_at->toRFC850String();
    }

    /**
     * Convert updated_at to a human readable format.
     *
     * @return string
     */
    public function getHumanTimeAttribute()
    {
        return $this->updated_at->diffForHumans();
    }

    /**
     * Get the pubdate value for RSS feeds.
     *
     * @return string
     */
    public function getPubdateAttribute()
    {
        return $this->updated_at->toRSSString();
    }

    /**
     * A link to the article, i.e. `/blog/1999/12/25/merry-christmas`.
     *
     * @return string
     */
    public function getLinkAttribute()
    {
        return '/blog/' . $this->updated_at->year . '/' . $this->updated_at->format('m') . '/' . $this->titleurl;
    }

    /**
     * Scope a query to only include articles from a particular year/month.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDate($query, $year = null, $month = null)
    {
        if ($year == null) {
            return $query;
        }
        $start = $year . '-01-01 00:00:00';
        $end = ($year + 1) . '-01-01 00:00:00';
        if (($month !== null) && ($month !== '12')) {
            $start = $year . '-' . $month . '-01 00:00:00';
            $end = $year . '-' . ($month + 1) . '-01 00:00:00';
        }
        if ($month === '12') {
            $start = $year . '-12-01 00:00:00';
            //$end as above
        }

        return $query->where([
            ['updated_at', '>=', $start],
            ['updated_at', '<', $end],
        ]);
    }
}
