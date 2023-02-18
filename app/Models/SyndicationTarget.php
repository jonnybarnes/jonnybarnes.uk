<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyndicationTarget extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'uid',
        'name',
        'service_name',
        'service_url',
        'service_photo',
        'user_name',
        'user_url',
        'user_photo',
    ];

    /** @var array<int, string> */
    protected $visible = [
        'uid',
        'name',
        'service',
        'user',
    ];

    /** @var array<int, string> */
    protected $appends = [
        'service',
        'user',
    ];

    protected function service(): Attribute
    {
        return Attribute::get(
            get: fn ($value, $attributes) => [
                'name' => $attributes['service_name'],
                'url' => $attributes['service_url'],
                'photo' => $attributes['service_photo'],
            ],
        );
    }

    protected function user(): Attribute
    {
        return Attribute::get(
            get: fn ($value, $attributes) => [
                'name' => $attributes['user_name'],
                'url' => $attributes['user_url'],
                'photo' => $attributes['user_photo'],
            ],
        );
    }
}
