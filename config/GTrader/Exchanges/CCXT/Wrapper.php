<?php

return [
    'name'          => 'CCXT',
    'long_name'     => 'CCXT',
    'children_ns'   => 'Exchanges',
    'default_child' => 'CCXT\\Supported',
    'user_options'  => [],
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
