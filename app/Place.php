<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Phaza\LaravelPostgis\Geometries\Point;
use MartinBean\Database\Eloquent\Sluggable;
use Phaza\LaravelPostgis\Geometries\Polygon;
use Phaza\LaravelPostgis\Eloquent\PostgisTrait;

class Place extends Model
{
    use PostgisTrait;

    /*
     * We want to turn the names into slugs.
     */
    use Sluggable;
    const DISPLAY_NAME = 'name';
    const SLUG = 'slug';

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
    protected $postgisFields = [Point::class, Polygon::class];

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
     * Get all places within a specified distance.
     *
     * @param  float latitude
     * @param  float longitude
     * @param  int maximum distance
     * @todo Check this shit.
     */
    public static function near(float $lat, float $lng, int $distance)
    {
        $point = $lng . ' ' . $lat;
        $distace = $distance ?? 1000;
        $places = DB::select(DB::raw("select
            name,
            slug,
            ST_AsText(location) AS location,
            ST_Distance(
                ST_GeogFromText('SRID=4326;POINT($point)'),
                location
            ) AS distance
        from places
        where ST_DWithin(
            ST_GeogFromText('SRID=4326;POINT($point)'),
            location,
            $distance
        ) ORDER BY distance"));

        return $places;
    }

    /**
     * Convert location to text.
     *
     * @param  text $value
     * @return text
     */
    public function getLocationAttribute($value)
    {
        $result = DB::select(DB::raw("SELECT ST_AsText('$value')"));

        return $result[0]->st_astext;
    }

    /**
     * Get the latitude from the `location` property.
     *
     * @return string latitude
     */
    public function getLatitudeAttribute()
    {
        preg_match('/\((.*)\)/', $this->location, $latlng);

        return explode(' ', $latlng[1])[1];
    }

    /**
     * Get the longitude from the `location` property.
     *
     * @return string longitude
     */
    public function getLongitudeAttribute()
    {
        preg_match('/\((.*)\)/', $this->location, $latlng);

        return explode(' ', $latlng[1])[0];
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
