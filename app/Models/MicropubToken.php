<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['token_hash', 'client_id', 'me', 'scope'])]
class MicropubToken extends Model
{
    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }

    /**
     * Find the active (non-revoked) token matching a raw bearer token value.
     *
     * Accepts mixed because callers pass request input directly, which PHP
     * lets be an array (e.g. a client sending token[]=a) - casting that to
     * string would throw, so anything non-string is just treated as absent.
     */
    public static function findActive(mixed $rawToken): ?self
    {
        if (! is_string($rawToken) || $rawToken === '') {
            return null;
        }

        return self::where('token_hash', hash('sha256', $rawToken))
            ->whereNull('revoked_at')
            ->first();
    }

    protected function isRevoked(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->revoked_at !== null,
        );
    }
}
