<?php

declare(strict_types=1);

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\{Builder, Collection, Model};
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * App\Models\Place.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $icon
 * @property string|null $foursquare
 * @property mixed|null $external_urls
 * @property float|null $latitude
 * @property float|null $longitude
 * @property-read string $longurl
 * @property-read string $shorturl
 * @property-read string $uri
 * @property-read Collection|\App\Models\Note[] $notes
 * @property-read int|null $notes_count
 * @method static Builder|Place findSimilarSlugs($attribute, $config, $slug)
 * @method static Builder|Place near($location, $distance = 1000)
 * @method static Builder|Place newModelQuery()
 * @method static Builder|Place newQuery()
 * @method static Builder|Place query()
 * @method static Builder|Place whereCreatedAt($value)
 * @method static Builder|Place whereDescription($value)
 * @method static Builder|Place whereExternalURL($url)
 * @method static Builder|Place whereExternalUrls($value)
 * @method static Builder|Place whereFoursquare($value)
 * @method static Builder|Place whereIcon($value)
 * @method static Builder|Place whereId($value)
 * @method static Builder|Place whereLatitude($value)
 * @method static Builder|Place whereLongitude($value)
 * @method static Builder|Place whereName($value)
 * @method static Builder|Place whereSlug($value)
 * @method static Builder|Place whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Place extends Model
{
    use Sluggable;

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
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
     * @return HasMany
     */
    public function notes()
    {
        return $this->hasMany('App\Models\Note');
    }

    /**
     * Select places near a given location.
     *
     * @param Builder $query
     * @param object $location
     * @param int $distance
     * @return Builder
     */
    public function scopeNear(Builder $query, object $location, int $distance = 1000): Builder
    {
        $haversine = "(6371 * acos(cos(radians($location->latitude))
                     * cos(radians(places.latitude))
                     * cos(radians(places.longitude)
                     - radians($location->longitude))
                     + sin(radians($location->latitude))
                     * sin(radians(places.latitude))))";

        return $query
            ->select() //pick the columns you want here.
            ->selectRaw("{$haversine} AS distance")
            ->whereRaw("{$haversine} < ?", [$distance]);
    }

    /**
     * Select places based on a URL.
     *
     * @param Builder $query
     * @param string $url
     * @return Builder
     */
    public function scopeWhereExternalURL(Builder $query, string $url): Builder
    {
        return $query->where('external_urls', '@>', json_encode([
            $this->getType($url) => $url,
        ]));
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
