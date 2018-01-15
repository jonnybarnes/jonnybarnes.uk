<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
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
     * @return  \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany('App\Models\Tag');
    }

    /**
     * The full url of a bookmark.
     *
     * @return string
     */
    public function getLongurlAttribute(): string
    {
        return config('app.url') . '/bookmarks/' . $this->id;
    }
}
