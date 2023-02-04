<?php

declare(strict_types=1);

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Place extends Model
{
    use HasFactory;
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
     * @param  Builder  $query
     * @param  object  $location
     * @param  int  $distance
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
     * @param  Builder  $query
     * @param  string  $url
     * @return Builder
     */
    public function scopeWhereExternalURL(Builder $query, string $url): Builder
    {
        return $query->where('external_urls', '@>', json_encode([
            $this->getType($url) => $url,
        ]));
    }

    protected function longurl(): Attribute
    {
        return Attribute::get(
            get: fn ($value, $attributes) => config('app.url') . '/places/' . $attributes['slug'],
        );
    }

    protected function shorturl(): Attribute
    {
        return Attribute::get(
            get: fn ($value, $attributes) => config('app.shorturl') . '/places/' . $attributes['slug'],
        );
    }

    protected function uri(): Attribute
    {
        return Attribute::get(
            get: fn () => $this->longurl,
        );
    }

    protected function externalUrls(): Attribute
    {
        return Attribute::set(
            set: function ($value, $attributes) {
                if ($value === null) {
                    return $attributes['external_urls'] ?? null;
                }

                $type = $this->getType($value);
                $already = [];

                if (array_key_exists('external_urls', $attributes)) {
                    $already = json_decode($attributes['external_urls'], true);
                }
                $already[$type] = $value;

                return json_encode($already);
            }
        );
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
