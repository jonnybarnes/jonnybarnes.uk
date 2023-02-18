<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bookmark extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = ['url', 'name', 'content'];

    /** @var array<string, string> */
    protected $casts = [
        'syndicates' => 'array',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Tag');
    }

    protected function longurl(): Attribute
    {
        return Attribute::get(
            get: fn () => config('app.url') . '/bookmarks/' . $this->id,
        );
    }
}
