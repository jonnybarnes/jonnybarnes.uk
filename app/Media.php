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
     * Our primaru key is a UUID value, therefore not incrementing.
     *
     * @var boolean
     */
    protected $incrementing = false;

    /**
     * Get the note that owns this media.
     */
    public function note()
    {
        return $this->belongsTo('App\Note');
    }
}
