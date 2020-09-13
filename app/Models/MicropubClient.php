<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\MicropubClient.
 *
 * @property int $id
 * @property string $client_url
 * @property string $client_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|\App\Models\Note[] $notes
 * @property-read int|null $notes_count
 * @method static Builder|MicropubClient newModelQuery()
 * @method static Builder|MicropubClient newQuery()
 * @method static Builder|MicropubClient query()
 * @method static Builder|MicropubClient whereClientName($value)
 * @method static Builder|MicropubClient whereClientUrl($value)
 * @method static Builder|MicropubClient whereCreatedAt($value)
 * @method static Builder|MicropubClient whereId($value)
 * @method static Builder|MicropubClient whereUpdatedAt($value)
 * @mixin Eloquent
 */
class MicropubClient extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['client_url', 'client_name'];

    /**
     * Define the relationship with notes.
     *
     * @return HasMany
     */
    public function notes(): HasMany
    {
        return $this->hasMany('App\Models\Note', 'client_id', 'client_url');
    }
}
