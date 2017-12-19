<?php

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
     */
    public function tags()
    {
        return $this->belongsToMany('App\Models\Tag');
    }

    /**
     * The full url of a bookmark.
     */
    public function getLongurlAttribute()
    {
        return config('app.url') . '/bookmarks/' . $this->id;
    }
}
