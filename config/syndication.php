<?php

/*
 * Here we define the syndication targets to be
 * returned by the micropub endpoint.
 */

return [
    // if you don’t have any targets, then set this to 'targets' => [];
    'targets' => [
        [
            'uid' => 'https://twitter.com/jonnybarnes',
            'name' => 'jonnybarnes on Twitter',
            'service' => [
                'name' => 'Twitter',
                'url' => 'https://twitter.com',
                'photo' => 'https://upload.wikimedia.org/wikipedia/en/9/9f/Twitter_bird_logo_2012.svg',
            ],
            'user' => [
                'name' => 'jonnybarnes',
                'url' => 'https://twitter.com/jonnybarnes',
                'photo' => 'https://pbs.twimg.com/profile_images/875422855932121089/W628ZI8w_400x400.jpg',
            ],
        ],
    ],
];
