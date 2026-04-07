<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['uid', 'name', 'service_name', 'service_url', 'service_photo', 'user_name', 'user_url', 'user_photo'])]
#[Visible(['uid', 'name', 'service', 'user'])]
#[Appends(['service', 'user'])]
class SyndicationTarget extends Model
{
    use HasFactory;

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
