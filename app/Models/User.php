<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /** @var array<int, string> */
    protected $fillable = [
        'name', 'password',
    ];

    /** @var array<int, string> */
    protected $hidden = [
        'current_password',
        'password',
        'remember_token',
    ];

    public function passkey(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }
}
