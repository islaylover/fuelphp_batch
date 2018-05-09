<?php

return [
    'api' => [
        'amazon' => [
            'shopping' => [
                'service_name'       => 'AWSECommerceService',
                'secret_access_key'  => 'yyyyyyyyyyyyyyyyyyy',
                'associate_tag'      => 'hogetaghogetaghogetag',
                'access_key'         => 'hogekeyhogekey',
                'api_usleep_milsec'  => 1500000,
                'memcached_host'     => 'localhost',
                'memcached_port'     => 11211,
                'cache_remain_sec'   => 60*60*72,
            ]
        ],
    ],
];
