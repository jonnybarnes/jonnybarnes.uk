<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterHtml;
use Codebird\Codebird;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;

class WebMention extends Model
{
    use FilterHtml;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'webmentions';

    /**
     * We shall set a blacklist of non-modifiable model attributes.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Define the relationship.
     *
     * @return MorphTo
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Get the author of the webmention.
     *
     * @return array
     * @throws AuthorshipParserException
     */
    public function getAuthorAttribute(): array
    {
        $authorship = new Authorship();
        $hCard = $authorship->findAuthor(json_decode($this->mf2, true));
        if (
            array_key_exists('properties', $hCard) &&
            array_key_exists('photo', $hCard['properties'])
        ) {
            $hCard['properties']['photo'][0] = $this->createPhotoLink($hCard['properties']['photo'][0]);
        }

        return $hCard;
    }

    /**
     * Get the published value for the webmention.
     *
     * @return string|null
     */
    public function getPublishedAttribute(): ?string
    {
        $mf2 = $this->mf2 ?? '';
        $microformats = json_decode($mf2, true);
        if (isset($microformats['items'][0]['properties']['published'][0])) {
            try {
                $published = carbon()->parse(
                    $microformats['items'][0]['properties']['published'][0]
                )->toDayDateTimeString();
            } catch (\Exception $exception) {
                $published = $this->updated_at->toDayDateTimeString();
            }
        } else {
            $published = $this->updated_at->toDayDateTimeString();
        }

        return $published;
    }

    /**
     * Get the filtered HTML of a reply.
     *
     * @return string|null
     */
    public function getReplyAttribute(): ?string
    {
        if ($this->mf2 === null) {
            return null;
        }
        $microformats = json_decode($this->mf2, true);
        if (isset($microformats['items'][0]['properties']['content'][0]['html'])) {
            return $this->filterHtml($microformats['items'][0]['properties']['content'][0]['html']);
        }

        return null;
    }

    /**
     * Create the photo link.
     *
     * @param  string
     * @return string
     */
    public function createPhotoLink(string $url): string
    {
        $url = normalize_url($url);
        $host = parse_url($url, PHP_URL_HOST);
        if ($host == 'pbs.twimg.com') {
            //make sure we use HTTPS, we know twitter supports it
            return str_replace('http://', 'https://', $url);
        }
        if ($host == 'twitter.com') {
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
