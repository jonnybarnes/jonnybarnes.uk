<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Contact.
 *
 * @property int $id
 * @property string $nick
 * @property string $name
 * @property string|null $homepage
 * @property string|null $twitter
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $facebook
 * @method static Builder|Contact newModelQuery()
 * @method static Builder|Contact newQuery()
 * @method static Builder|Contact query()
 * @method static Builder|Contact whereCreatedAt($value)
 * @method static Builder|Contact whereFacebook($value)
 * @method static Builder|Contact whereHomepage($value)
 * @method static Builder|Contact whereId($value)
 * @method static Builder|Contact whereName($value)
 * @method static Builder|Contact whereNick($value)
 * @method static Builder|Contact whereTwitter($value)
 * @method static Builder|Contact whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Contact extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'contacts';

    /**
     * We shall guard against mass-migration.
     *
     * @var array
     */
    protected $fillable = ['nick', 'name', 'homepage', 'twitter', 'facebook'];
}
