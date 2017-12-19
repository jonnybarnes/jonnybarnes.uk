<?php

namespace App\Models;

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
    protected $fillable = ['token', 'path', 'type', 'image_widths'];

    /**
     * Get the note that owns this media.
     */
    public function note()
    {
        return $this->belongsTo('App\Models\Note');
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
        $basename = $this->getBasename($this->path);
        $extension = $this->getExtension($this->path);

        return config('filesystems.disks.s3.url') . '/' . $basename . '-medium.' . $extension;
    }

    /**
     * Get the URL for the small size of an S3 image file.
     *
     * @return string
     */
    public function getSmallurlAttribute()
    {
        $basename = $this->getBasename($this->path);
        $extension = $this->getExtension($this->path);

        return config('filesystems.disks.s3.url') . '/' . $basename . '-small.' . $extension;
    }

    public function getBasename($path)
    {
        // the following achieves this data flow
        // foo.bar.png => ['foo', 'bar', 'png'] => ['foo', 'bar'] => foo.bar
        $filenameParts = explode('.', $path);
        array_pop($filenameParts);
        $basename = ltrim(array_reduce($filenameParts, function ($carry, $item) {
            return $carry . '.' . $item;
        }, ''), '.');

        return $basename;
    }

    public function getExtension($path)
    {
        $parts = explode('.', $path);

        return array_pop($parts);
    }
}
