<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_endpoint';

    /**
     * Get the note that owns this media.
     */
    public function note()
    {
        return $this->belongsTo('App\Note');
    }

    /**
     * Get the URL for an S3 media file.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return config('filesystems.s3.url') . '/' . $this->path;
    }
}
