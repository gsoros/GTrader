<?php

/*
    Note: remove extreme values e.g.:
        update candles set high = greatest(open, close) where high > 1000 and
        exchange_id = (select id from exchanges where name = 'OKCoin_ETH_Spot');
*/

return [
    'short_name'        => 'OKCES',
    'local_name'        => 'OKCoin_ETH_Spot',   // class name, also used in the local database
    'user_options'      => [                    // user-configurable options
        'api_key' => '',                        // API key
        'api_secret' => '',                     // API secret
        'position_size' => 20,                  // max percentage of balance to be used in a position
        'market_orders' => 0,                   // 1: use market orders, 0: use limit orders
    ],
    'symbols' => [
        'eth_usd' => [                              // used in the local database, same as symbolname.local_name
            'short_name' => 'ETHUSD',               // used for displaying in lists
            'local_name' => 'eth_usd',              // used in the local database, same as the key
            'remote_name' => 'eth_usd',             // used when querying the remote data
            'resolutions'=> [
                60      => '1m',
                180     => '3m',
                300     => '5m',
                900     => '15m',
                1800    => '30m',
                3600    => '1h',
                7200    => '2h',
                14400   => '4h',
                21600   => '6h',
                43200   => '12h',
                86400   => '1d',
                259200  => '3d',
                604800  => '1w'
            ],
        ],
    ],
];
