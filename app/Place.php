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

    /*
     * Convert location to text.
     *
     * @param  text $value
     * @return text
     *
    public function getLocationAttribute($value)
    {
        $result = DB::select(DB::raw("SELECT ST_AsText('$value')"));

        return $result[0]->st_astext;
    }*/

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
}
