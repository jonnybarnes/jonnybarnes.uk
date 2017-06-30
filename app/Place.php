<?php

namespace App;

use DB;
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
    public function sluggable()
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
     * @var array
     */
    public function notes()
    {
        return $this->hasMany('App\Note');
    }

    /**
     * Select places near a given location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  Point $point
     * @param  int Distance
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNear(Builder $query, Point $point, $distance = 1000)
    {
        $field = DB::raw(
            sprintf("ST_Distance(%s.location, ST_GeogFromText('%s'))",
                $this->getTable(),
                $point->toWKT()
            )
        );

        return $query->where($field, '<=', $distance)->orderBy($field);
    }

    public function scopeWhereExternalURL(Builder $query, string $url)
    {
        $type = $this->getType($url);
        if ($type === null) {
            // we haven’t set a type, therefore result must be empty set
            // id can’t be null, so this will return empty set
            return $query->whereNull('id');
        }

        return $query->where('external_urls', '@>', json_encode([
            $type => $url,
        ]));
    }

    /**
     * Get the latitude from the `location` property.
     *
     * @return string latitude
     */
    public function getLatitudeAttribute()
    {
        return explode(' ', $this->location)[1];
    }

    /**
     * Get the longitude from the `location` property.
     *
     * @return string longitude
     */
    public function getLongitudeAttribute()
    {
        return explode(' ', $this->location)[0];
    }

    /**
     * The Long URL for a place.
     *
     * @return string
     */
    public function getLongurlAttribute()
    {
        return config('app.url') . '/places/' . $this->slug;
    }

    /**
     * The Short URL for a place.
     *
     * @return string
     */
    public function getShorturlAttribute()
    {
        return config('app.shorturl') . '/places/' . $this->slug;
    }

    public function setExternalUrlsAttribute($value)
    {
        $type = $this->getType($value);
        if ($type === null) {
            throw new \Exception('Unkown external url type');
        }
        $already = [];
        if (array_key_exists('external_urls', $this->attributes)) {
            $already = json_decode($this->attributes['external_urls'], true);
        }
        $already[$type] = $value;
        $this->attributes['external_urls'] = json_encode($already);
    }

    private function getType(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (ends_with($host, 'foursquare.com') === true) {
            return 'foursquare';
        }
        if (ends_with($host, 'openstreetmap.org') === true) {
            return 'osm';
        }

        return null;
    }
}
