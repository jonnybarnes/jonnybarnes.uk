<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterHtml;
use Codebird\Codebird;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Jonnybarnes\WebmentionsParser\Authorship;

class WebMention extends Model
{
    use FilterHtml;
    use HasFactory;

    /** @var string */
    protected $table = 'webmentions';

    /** @var array<int, string> */
    protected $guarded = ['id'];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function author(): Attribute
    {
        return Attribute::get(
            get: function ($value, $attributes) {
                if (
                    ! array_key_exists('mf2', $attributes) ||
                    $attributes['mf2'] === null
                ) {
                    return null;
                }

                $authorship = new Authorship();
                $hCard = $authorship->findAuthor(json_decode($attributes['mf2'], true));

                if ($hCard === false) {
                    return null;
                }

                if (
                    array_key_exists('properties', $hCard) &&
                    array_key_exists('photo', $hCard['properties'])
                ) {
                    $hCard['properties']['photo'][0] = $this->createPhotoLink($hCard['properties']['photo'][0]);
                }

                return $hCard;
            }
        );
    }

    protected function published(): Attribute
    {
        return Attribute::get(
            get: function ($value, $attributes) {
                $mf2 = $attributes['mf2'] ?? '';
                $microformats = json_decode($mf2, true);
                if (isset($microformats['items'][0]['properties']['published'][0])) {
                    try {
                        $published = carbon()->parse(
                            $microformats['items'][0]['properties']['published'][0]
                        )->toDayDateTimeString();
                    } catch (Exception) {
                        $published = $this->updated_at->toDayDateTimeString();
                    }
                } else {
                    $published = $this->updated_at->toDayDateTimeString();
                }

                return $published;
            }
        );
    }

    protected function reply(): Attribute
    {
        return Attribute::get(
            get: function ($value, $attributes) {
                if (
                    ! array_key_exists('mf2', $attributes) ||
                    $attributes['mf2'] === null
                ) {
                    return null;
                }

                $microformats = json_decode($attributes['mf2'], true);

                if (isset($microformats['items'][0]['properties']['content'][0]['html'])) {
                    return $this->filterHtml($microformats['items'][0]['properties']['content'][0]['html']);
                }

                return null;
            }
        );
    }

    /**
     * Create the photo link.
     */
    public function createPhotoLink(string $url): string
    {
        $url = normalize_url($url);
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === 'pbs.twimg.com') {
            //make sure we use HTTPS, we know twitter supports it
            return str_replace('http://', 'https://', $url);
        }

        if ($host === 'twitter.com') {
            if (Cache::has($url)) {
                return Cache::get($url);
            }
            $username = ltrim(parse_url($url, PHP_URL_PATH), '/');
            $codebird = resolve(Codebird::class);
            $info = $codebird->users_show(['screen_name' => $username]);
            $profile_image = $info->profile_image_url_https;
            Cache::put($url, $profile_image, 10080); //1 week

            return $profile_image;
        }

        $filesystem = new Filesystem();
        if ($filesystem->exists(public_path() . '/assets/profile-images/' . $host . '/image')) {
            return '/assets/profile-images/' . $host . '/image';
        }

        return $url;
    }
}
