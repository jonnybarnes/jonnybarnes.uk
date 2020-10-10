<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * App\Models\Media.
 *
 * @property int $id
 * @property string|null $token
 * @property string $path
 * @property string $type
 * @property int|null $note_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $image_widths
 * @property-read string $mediumurl
 * @property-read string $smallurl
 * @property-read string $url
 * @property-read Note|null $note
 * @method static Builder|Media newModelQuery()
 * @method static Builder|Media newQuery()
 * @method static Builder|Media query()
 * @method static Builder|Media whereCreatedAt($value)
 * @method static Builder|Media whereId($value)
 * @method static Builder|Media whereImageWidths($value)
 * @method static Builder|Media whereNoteId($value)
 * @method static Builder|Media wherePath($value)
 * @method static Builder|Media whereToken($value)
 * @method static Builder|Media whereType($value)
 * @method static Builder|Media whereUpdatedAt($value)
 * @mixin Eloquent
 */
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
     *
     * @return BelongsTo
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo('App\Models\Note');
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
     * @param string $path
     * @return string
     */
    public function getBasename(string $path): string
    {
        // the following achieves this data flow
        // foo.bar.png => ['foo', 'bar', 'png'] => ['foo', 'bar'] => foo.bar
        $filenameParts = explode('.', $path);
        array_pop($filenameParts);

        return ltrim(array_reduce($filenameParts, function ($carry, $item) {
            return $carry . '.' . $item;
        }, ''), '.');
    }

    /**
     * Get the extension from a given filename.
     *
     * @param string $path
     * @return string
     */
    public function getExtension(string $path): string
    {
        $parts = explode('.', $path);

        return array_pop($parts);
    }
}
