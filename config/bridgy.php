<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mastodon Token
    |--------------------------------------------------------------------------
    |
    | When syndicating posts to Mastodon using Brid.gy’s Micropub endpoint, we
    | need to provide an access token. This token can be generated by going to
    | https://brid.gy/mastodon and clicking the “Get token” button.
    |
    */

    'mastodon_token' => env('BRIDGY_MASTODON_TOKEN'),

];
