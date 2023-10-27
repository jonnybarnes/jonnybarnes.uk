<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passkey extends Model
{
    use HasFactory;

    /** @inerhitDoc */
    protected $fillable = [
        'passkey_id',
        'passkey',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
