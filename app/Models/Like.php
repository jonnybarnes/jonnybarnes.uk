<?php

declare(strict_types=1);

namespace App\Models;

use Mf2;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = ['url'];

    /**
     * Normalize the URL of a Like.
     *
     * @param  string  $value The provided URL
     */
    public function setUrlAttribute(string $value)
    {
        $this->attributes['url'] = normalize_url($value);
    }

    /**
     * Normalize the URL of the author of the like.
     *
     * @param  string  $value The author’s url
     */
    public function setAuthorUrlAttribute(?string $value)
    {
        $this->attributes['author_url'] = normalize_url($value);
    }

    /**
     * If the content contains HTML, filter it.
     *
     * @param  string  $value The content of the like
     * @return string|null
     */
    public function getContentAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $mf2 = Mf2\parse($value, $this->url);

        if (array_get($mf2, 'items.0.properties.content.0.html')) {
            return $this->filterHTML(
                $mf2['items'][0]['properties']['content'][0]['html']
            );
        }

        return $value;
    }

    /**
     * Filter some HTML with HTMLPurifier.
     *
     * @param  string  $html
     * @return string
     */
    private function filterHTML(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path() . '/HTMLPurifier');
        $config->set('HTML.TargetBlank', true);
        $purifier = new HTMLPurifier($config);

        return $purifier->purify($html);
    }
}
