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

    /**
     * Get the URL for the medium size of an S3 image file.
     *
     * @return string
     */
    public function getMediumurlAttribute()
    {
        $filenameParts = explode('.', $this->path);
        $extension = array_pop($filenameParts);
        // the following acheives this data flow
        // foo.bar.png => ['foo', 'bar', 'png'] => ['foo', 'bar'] => foo.bar
        $basename = ltrim(array_reduce($filenameParts, function ($carry, $item) {
            return $carry . '.' . $item;
        }, ''), '.');

        return config('filesystems.disks.s3.url') . '/' . $basename . '-medium.' . $extension;
    }

    /**
     * Get the URL for the small size of an S3 image file.
     *
     * @return string
     */
    public function getSmallurlAttribute()
    {
        $filenameParts = explode('.', $this->path);
        $extension = array_pop($filenameParts);
        // the following acheives this data flow
        // foo.bar.png => ['foo', 'bar', 'png'] => ['foo', 'bar'] => foo.bar
        $basename = ltrim(array_reduce($filenameParts, function ($carry, $item) {
            return $carry . '.' . $item;
        }, ''), '.');

        return config('filesystems.disks.s3.url') . '/' . $basename . '-small.' . $extension;
    }
}
