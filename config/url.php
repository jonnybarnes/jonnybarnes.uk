<?php

/*
 * Here we set the long and short URLs our app shall use
 * You can override these settings in the .env file
 */

return [
    'longurl' => env('APP_LONGURL', 'jonnybarnes.uk'),
    'shorturl' => env('APP_SHORTURL', 'jmb.so')
];