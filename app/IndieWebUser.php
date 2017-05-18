<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndieWebUser extends Model
{
    /**
     * Mass assignment protection.
     *
     * @var array
     */
    protected $fillable = ['me'];
}
