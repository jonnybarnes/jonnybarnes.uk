<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MicropubClient extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'clients';

    /** @var array<int, string> */
    protected $fillable = ['client_url', 'client_name'];

    public function notes(): HasMany
    {
        return $this->hasMany('App\Models\Note', 'client_id', 'client_url');
    }
}
