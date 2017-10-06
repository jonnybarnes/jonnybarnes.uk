<?php

namespace App;

use Mf2;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = ['url'];

    public function setUrlAttribute($value)
    {
        $this->attributes['url'] = normalize_url($value);
    }

    public function setAuthorUrlAttribute($value)
    {
        $this->attributes['author_url'] = normalize_url($value);
    }

    public function getContentAttribute($value)
    {
        if ($value === null) {
            return $this->url;
        }

        $mf2 = Mf2\parse($value, $this->url);

        return $this->filterHTML($mf2['items'][0]['properties']['content'][0]['html']);
    }

    public function filterHTML($html)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path() . '/HTMLPurifier');
        $config->set('HTML.TargetBlank', true);
        $purifier = new HTMLPurifier($config);

        return $purifier->purify($html);
    }
}
