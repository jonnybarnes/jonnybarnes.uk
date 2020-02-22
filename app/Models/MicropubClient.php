<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\MicropubClient
 *
 * @property int $id
 * @property string $client_url
 * @property string $client_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Note[] $notes
 * @property-read int|null $notes_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MicropubClient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MicropubClient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MicropubClient query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MicropubClient whereClientName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MicropubClient whereClientUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MicropubClient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MicropubClient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MicropubClient whereUpdatedAt($value)
 * @mixin \Eloquent
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
