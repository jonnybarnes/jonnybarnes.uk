<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Cviebrock\EloquentSluggable\Sluggable;
use Phaza\LaravelPostgis\Geometries\Point;
use Phaza\LaravelPostgis\Eloquent\PostgisTrait;

class Place extends Model
{
    use Sluggable;
    use PostgisTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug'];

    /**
     * The attributes that are Postgis geometry objects.
     *
     * @var array
     */
    protected $postgisFields = [
        'location',
        'polygon',
    ];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => true,
            ],
        ];
    }

    /**
     * Define the relationship with Notes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany('App\Models\Note');
    }

    /**
     * Select places near a given location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Phaza\LaravelPostgis\Geometries\Point  $point
     * @param  int  $distance
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNear(Builder $query, Point $point, $distance = 1000): Builder
    {
        $field = DB::raw(
            sprintf(
                "ST_Distance(%s.location, ST_GeogFromText('%s'))",
                $this->getTable(),
                $point->toWKT()
            )
        );

        return $query->where($field, '<=', $distance)->orderBy($field);
    }

    /**
     * Select places based on a URL.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $url
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereExternalURL(Builder $query, string $url): Builder
    {
        return $query->where('external_urls', '@>', json_encode([
            $this->getType($url) => $url,
        ]));
    }

    /**
     * Get the latitude from the `location` property.
     *
     * @return float
     */
    public function getLatitudeAttribute(): float
    {
        return $this->location->getLat();
    }

    /**
     * Get the longitude from the `location` property.
     *
     * @return float
     */
    public function getLongitudeAttribute(): float
    {
        return $this->location->getLng();
    }

    /**
     * The Long URL for a place.
     *
     * @return string
     */
    public function getLongurlAttribute(): string
    {
        return config('app.url') . '/places/' . $this->slug;
    }

    /**
     * The Short URL for a place.
     *
     * @return string
     */
    public function getShorturlAttribute(): string
    {
        return config('app.shorturl') . '/places/' . $this->slug;
    }

    /**
     * This method is an alternative for `longurl`.
     *
     * @return string
     */
    public function getUriAttribute(): string
    {
        return $this->longurl;
    }

    /**
     * Dealing with a jsonb column, so we check input first.
     *
     * @param  string|null  $url
     */
    public function setExternalUrlsAttribute(?string $url)
    {
        if ($url === null) {
            return;
        }
        $type = $this->getType($url);
        $already = [];
        if (array_key_exists('external_urls', $this->attributes)) {
            $already = json_decode($this->attributes['external_urls'], true);
        }
        $already[$type] = $url;
        $this->attributes['external_urls'] = json_encode($already);
    }

    /**
     * Given a URL, see if it is one of our known types.
     *
     * @param  string  $url
     * @return string
     */
    private function getType(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (Str::endsWith($host, 'foursquare.com') === true) {
            return 'foursquare';
        }
        if (Str::endsWith($host, 'openstreetmap.org') === true) {
            return 'osm';
        }

        return 'default';
    }
}
