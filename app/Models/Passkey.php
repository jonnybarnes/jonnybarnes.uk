<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passkey extends Model
{
    use HasFactory;

    /**
     * Save and access the passkey appropriately.
     */
    protected function passkey(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => stream_get_contents($value),
            set: static fn ($value) => pg_escape_bytea($value),
        );
    }

    /**
     * Save and access the transports appropriately.
     */
    protected function transports(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            set: static fn ($value) => json_encode($value, JSON_THROW_ON_ERROR),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
