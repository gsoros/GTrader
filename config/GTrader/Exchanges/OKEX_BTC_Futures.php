<?php

return [
    'long_name'         => 'OKEX BTC Futures',
    'short_name'        => 'OKXBF',
    'local_name'        => 'OKEX_BTC_Futures',  // class name, also used in the local database
    'user_options'      => [                    // user-configurable options
        'api_key' => '',                        // API key
        'api_secret' => '',                     // API secret
        'position_size' => 20,                  // max percentage of balance to be used in a position
        'leverage' => 10,                       // 10 or 20
        'max_contracts' => 100,                 // max amount of contracts to hold
        'market_orders' => 0,                   // 1: use market orders, 0: use limit orders
    ],
    'symbols' => [
        'btc_usd_3m' => [                                       // used in the local database, same as symbolname.local_name
            'long_name' => 'Bitcoin - US Dollar Quarterly',
            'short_name' => 'BTCUSD3M',                         // used for displaying in lists
            'local_name' => 'btc_usd_3m',                       // used in the local database, same as the key
            'remote_name' => 'btc_usd',                         // used when querying the remote data
            'contract_value' => 100,                            // value of 1 contract
            'resolutions'=> [
                60     => '1m',
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
            'contract_type' => 'quarter',                       // used when querying the remote data
        ],
    ],
];
