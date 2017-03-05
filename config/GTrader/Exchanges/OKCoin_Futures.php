<?php

return [
    'long_name'         => 'OKCoin Futures Exchange',
    'short_name'        => 'OKCF',
    'local_name'        => 'OKCoin_Futures',                    // class name, also used in the local database
    'user_config_keys'  => [                                    // user-configurable parameters
                            'api_key' => '',                    // API key
                            'api_secret' => '',                 // API secret
                            'position_size' => 20,              // max percentage of balance to be used in a position
                            'leverage' => 10,                   // 10 or 20
                            'max_contracts' => 100,             // max amount of contracts to hold
                            'market_orders' => 0,               // 1: use market orders, 0: use limit orders
                            ],
    'symbols' => [
        'btc_usd_3m' =>                                         // used in the local database, same as local_name
            [
            'long_name' => 'Bitcoin - US Dollar Quarterly',
            'short_name' => 'BTCUSD3M',                         // used for displaying in lists
            'local_name' => 'btc_usd_3m',                       // used in the local database, same as the key
            'remote_name' => 'btc_usd',                         // used when querying the remote data
            'contract_value' => 100,                            // value of 1 contract
            'resolutions'=> [60     => '1 minute',
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
                            604800  => '1 week'],
            'contract_type' => 'quarter',                       // used when querying the remote data
            ],
    ],
    /* OKCoin-specific resolution strings */
    'resolution_names' => [ 60      => '1min',
                            180     => '3min',   //       3*60
                            300     => '5min',   //       5*60
                            900     => '15min',  //      15*60
                            1800    => '30min',  //      30*60
                            3600    => '1hour',  //      60*60
                            7200    => '2hour',  //    2*60*60
                            14400   => '4hour',  //    4*60*60
                            21600   => '6hour',  //    6*60*60
                            43200   => '12hour', //   12*60*60
                            86400   => '1day',   //   24*60*60
                            259200  => '3day',   // 3*24*60*60
                            604800  => '1week'], // 7*24*60*60

];
