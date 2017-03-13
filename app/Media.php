<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_endpoint';

    /**
     * Get the note that owns this media.
     */
    public function note()
    {
        return $this->belongsTo('App\Note');
    }
}
