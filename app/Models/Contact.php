<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

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

    protected function photo(): Attribute
    {
        $photo = '/assets/profile-images/default-image';

        if (array_key_exists('homepage', $this->attributes) && ! empty($this->attributes['homepage'])) {
            $host = parse_url($this->attributes['homepage'], PHP_URL_HOST);
            if (file_exists(public_path() . '/assets/profile-images/' . $host . '/image')) {
                $photo = '/assets/profile-images/' . $host . '/image';
            }
        }

        return Attribute::make(
            get: fn () => $photo,
        );
    }
}
