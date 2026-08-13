<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Table('micropub_tokens')]
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
     * Find the active (non-revoked) token matching a raw bearer token string.
     */
    public static function findActive(string $rawToken): ?self
    {
        if ($rawToken === '') {
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
