<?php

return [
    'name'          => 'CCXTWrapper',
    'long_name'     => 'CCXT',
    'user_options'  => [
        'symbols' => [],
    ],
    'symbols' => [
        'BTC/USD' => [                           
            'name'          => 'BTC/USD',
            'long_name'     => 'BTC/USD',
            'resolutions'   => [
                3600 => '1h',
            ],
        ],
    ],
];
