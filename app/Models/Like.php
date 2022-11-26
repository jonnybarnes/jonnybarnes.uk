<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterHtml;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Mf2;

class Like extends Model
{
    use FilterHtml;
    use HasFactory;

    protected $fillable = ['url'];

    protected function url(): Attribute
    {
        return Attribute::set(
            set: fn ($value) => normalize_url($value),
        );
    }

    protected function authorUrl(): Attribute
    {
        return Attribute::set(
            set: fn ($value) => normalize_url($value),
        );
    }

    protected function content(): Attribute
    {
        return Attribute::get(
            get: function ($value, $attributes) {
                if ($value === null) {
                    return null;
                }

                $mf2 = Mf2\parse($value, $attributes['url']);

                if (Arr::get($mf2, 'items.0.properties.content.0.html')) {
                    return $this->filterHtml(
                        $mf2['items'][0]['properties']['content'][0]['html']
                    );
                }

                return $value;
            }
        );
    }
}
