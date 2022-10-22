<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyndicationTarget extends Model
{
    use HasFactory;

    /**
     * The attributes that are visible when serializing the model.
     *
     * @var array<string>
     */
    protected $visible = [
        'uid',
        'name',
        'service',
        'user',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'service',
        'user',
    ];

    /**
     * Get the service data as a single attribute.
     *
     * @vreturn Attribute
     */
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

    /**
     * Get the user data as a single attribute.
     *
     * @vreturn Attribute
     */
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
