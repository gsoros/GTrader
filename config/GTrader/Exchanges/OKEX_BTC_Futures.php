<?php

return [
    'long_name'         => 'OKEX BTC Futures',
    'short_name'        => 'OKEF',
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
                60     => '1 minute',
                180     => '3 minutes',
                300     => '5 minutes',
                900     => '15 minutes',
                1800    => '30 minutes',
                3600    => '1 hour',
                7200    => '2 hours',
                14400   => '4 hours',
                21600   => '6 hours',
                43200   => '12 hours',
                86400   => '1 day',
                259200  => '3 days',
                604800  => '1 week'
            ],
            'contract_type' => 'quarter',                       // used when querying the remote data
        ],
    ],
];
