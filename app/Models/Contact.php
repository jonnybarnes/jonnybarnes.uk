<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
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
}
