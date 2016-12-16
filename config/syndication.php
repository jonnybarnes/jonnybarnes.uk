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
                'photo' => 'https://pbs.twimg.com/profile_images/1853565405/jmb-bw.jpg',
            ],
        ],
        [
            'uid' => 'https://facebook.com/jonnybarnes',
            'name' => 'jonnybarnes on Facebook',
            'service' => [
                'name' => 'Facebook',
                'url' => 'https://facebook.com',
                'photo' => 'https://en.facebookbrand.com/wp-content/uploads/2016/05/FB-fLogo-Blue-broadcast-2.png',
            ],
            'user' => [
                'name' => 'jonnybarnes',
                'url' => 'https://facebook.com/jonnybarnes',
            ],
        ]
    ]
];
