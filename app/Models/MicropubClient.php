<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('clients')]
#[Fillable(['client_url', 'client_name'])]
class MicropubClient extends Model
{
    use HasFactory;

    public function notes(): HasMany
    {
        return $this->hasMany('App\Models\Note', 'client_id', 'client_url');
    }
}
