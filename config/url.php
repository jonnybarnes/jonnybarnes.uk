<?php

/*
 * Here we set the long and short URLs our app shall use
 * You can override these settings in the .env file
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Application Long URL
    |--------------------------------------------------------------------------
    |
    | The long URL for the application
    |
    */

    'longurl' => env('APP_LONGURL', 'longurl.local'),

    /*
    |--------------------------------------------------------------------------
    | Application Short URL
    |--------------------------------------------------------------------------
    |
    | The short URL for the application
    |
    */

    'shorturl' => env('APP_SHORTURL', 'shorturl.local'),

];
