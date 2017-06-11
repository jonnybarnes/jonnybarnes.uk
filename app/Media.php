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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['path'];

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
        if (starts_with($this->path, 'https://')) {
            return $this->path;
        }

        return config('filesystems.disks.s3.url') . '/' . $this->path;
    }
}
