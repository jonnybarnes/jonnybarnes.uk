<?php

// You can find the keys here : https://dev.twitter.com/

return [
    'API_URL' => 'api.twitter.com',
    'API_VERSION' => '1.1',
    'AUTHENTICATE_URL' => 'https://api.twitter.com/oauth/authenticate',
    'AUTHORIZE_URL' => 'https://api.twitter.com/oauth/authorize',
    'ACCESS_TOKEN_URL' => 'oauth/access_token',
    'REQUEST_TOKEN_URL' => 'oauth/request_token',
    'USE_SSL' => true,

    'CONSUMER_KEY' => env('TWITTER_CONSUMER_KEY'),
    'CONSUMER_SECRET' => env('TWITTER_CONSUMER_SECRET'),
    'ACCESS_TOKEN' => env('TWITTER_ACCESS_TOKEN'),
    'ACCESS_TOKEN_SECRET' => env('TWITTER_ACCESS_TOKEN_SECRET'),
];
