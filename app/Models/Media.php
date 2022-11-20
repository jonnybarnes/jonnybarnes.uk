<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Media extends Model
{
    use HasFactory;

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
     *
     * @return BelongsTo
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    /**
     * Get the URL for an S3 media file.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        if (Str::startsWith($this->path, 'https://')) {
            return $this->path;
        }

        return config('filesystems.disks.s3.url') . '/' . $this->path;
    }

    /**
     * Get the URL for the medium size of an S3 image file.
     *
     * @return string
     */
    public function getMediumurlAttribute(): string
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
    public function getSmallurlAttribute(): string
    {
        $basename = $this->getBasename($this->path);
        $extension = $this->getExtension($this->path);

        return config('filesystems.disks.s3.url') . '/' . $basename . '-small.' . $extension;
    }

    /**
     * Give the real part of a filename, i.e. strip the file extension.
     *
     * @param  string  $path
     * @return string
     */
    public function getBasename(string $path): string
    {
        // the following achieves this data flow
        // foo.bar.png => ['foo', 'bar', 'png'] => ['foo', 'bar'] => foo.bar
        $filenameParts = explode('.', $path);
        array_pop($filenameParts);

        return ltrim(array_reduce($filenameParts, static function ($carry, $item) {
            return $carry . '.' . $item;
        }, ''), '.');
    }

    /**
     * Get the extension from a given filename.
     *
     * @param  string  $path
     * @return string
     */
    public function getExtension(string $path): string
    {
        $parts = explode('.', $path);

        return array_pop($parts);
    }

    /**
     * Get the mime type of the media file.
     *
     * For now we will just use the extension, but this could be improved.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        $extension = $this->getExtension($this->path);

        return match ($extension) {
            'gif' => 'image/gif',
            'jpeg', 'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'tiff' => 'image/tiff',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mkv' => 'video/mkv',
            default => 'application/octet-stream',
        };
    }
}
