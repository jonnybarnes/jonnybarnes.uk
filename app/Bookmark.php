<?php

namespace App;

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
     * The tags that belong to the bookmark.
     */
    public function tags()
    {
        return $this->belongsToMany('App\Tag');
    }
}
