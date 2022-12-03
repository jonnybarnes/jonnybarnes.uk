<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function url(): Attribute
    {
        return Attribute::get(
            get: function ($value, $attributes) {
                if (Str::startsWith($attributes['path'], 'https://')) {
                    return $attributes['path'];
                }

                return config('filesystems.disks.s3.url') . '/' . $attributes['path'];
            }
        );
    }

    protected function mediumurl(): Attribute
    {
        return Attribute::get(
            get: fn ($value, $attributes) => $this->getSizeUrl($attributes['path'], 'medium'),
        );
    }

    protected function smallurl(): Attribute
    {
        return Attribute::get(
            get: fn ($value, $attributes) => $this->getSizeUrl($attributes['path'], 'small'),
        );
    }

    protected function mimetype(): Attribute
    {
        return Attribute::get(
            get: function ($value, $attributes) {
                $extension = $this->getExtension($attributes['path']);

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
            },
        );
    }

    private function getSizeUrl(string $path, string $size): string
    {
        $basename = $this->getBasename($path);
        $extension = $this->getExtension($path);

        return config('filesystems.disks.s3.url') . '/' . $basename . '-' . $size . '.' . $extension;
    }

    private function getBasename(string $path): string
    {
        // the following achieves this data flow
        // foo.bar.png => ['foo', 'bar', 'png'] => ['foo', 'bar'] => foo.bar
        $filenameParts = explode('.', $path);
        array_pop($filenameParts);

        return ltrim(array_reduce($filenameParts, static function ($carry, $item) {
            return $carry . '.' . $item;
        }, ''), '.');
    }

    private function getExtension(string $path): string
    {
        $parts = explode('.', $path);

        return array_pop($parts);
    }
}
