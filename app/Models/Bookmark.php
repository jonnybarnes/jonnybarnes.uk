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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['url', 'name', 'content'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'syndicates' => 'array',
    ];

    /**
     * The tags that belong to the bookmark.
     *
     * @return  BelongsToMany
     */
    public function tags()
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
