<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WebMention extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'webmentions';

    /**
     * Define the relationship.
     *
     * @var array
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * We shall set a blacklist of non-modifiable model attributes.
     *
     * @var array
     */
    protected $guarded = ['id'];
}
