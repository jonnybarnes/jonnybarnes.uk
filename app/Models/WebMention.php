<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FilterHtml;
use Codebird\Codebird;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Jonnybarnes\WebmentionsParser\Authorship;
use Jonnybarnes\WebmentionsParser\Exceptions\AuthorshipParserException;

/**
 * App\Models\WebMention.
 *
 * @property int $id
 * @property string $source
 * @property string $target
 * @property int|null $commentable_id
 * @property string|null $commentable_type
 * @property string|null $type
 * @property string|null $content
 * @property int $verified
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property mixed|null $mf2
 * @property-read WebMention|null $commentable
 * @property-read array $author
 * @property-read string|null $published
 * @property-read string|null $reply
 * @method static Builder|WebMention newModelQuery()
 * @method static Builder|WebMention newQuery()
 * @method static Builder|WebMention query()
 * @method static Builder|WebMention whereCommentableId($value)
 * @method static Builder|WebMention whereCommentableType($value)
 * @method static Builder|WebMention whereContent($value)
 * @method static Builder|WebMention whereCreatedAt($value)
 * @method static Builder|WebMention whereDeletedAt($value)
 * @method static Builder|WebMention whereId($value)
 * @method static Builder|WebMention whereMf2($value)
 * @method static Builder|WebMention whereSource($value)
 * @method static Builder|WebMention whereTarget($value)
 * @method static Builder|WebMention whereType($value)
 * @method static Builder|WebMention whereUpdatedAt($value)
 * @method static Builder|WebMention whereVerified($value)
 * @mixin Eloquent
 */
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
     * @param string $url
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
