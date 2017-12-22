<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MicropubClient extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['client_url', 'client_name'];

    /**
     * Define the relationship with notes.
     *
     * @return void
     */
    public function notes()
    {
        return $this->hasMany('App\Models\Note', 'client_id', 'client_url');
    }
}
