<?php

return [
    'long_name' => 'OKCoin Futures',
    'short_name' => 'OKCF',
    'local_name' => 'okcoin_futures',                       // used in the local database
    'symbols' => [
        'btc_usd_3m' =>                                         // used in the local database, same as local_name
            [
            'long_name' => 'Bitcoin - US Dollar Quarterly',
            'short_name' => 'BTCUSD3M',                         // used for displaying in lists
            'local_name' => 'btc_usd_3m',                       // used in the local database, same as the key
            'remote_name' => 'btc_usd',                         // used when querying the remote data
            'resolutions' => [60, 180, 300, 900, 1800, 3600,
                7200, 14400, 21600, 43200, 86400, 259200, 604800],
            'contract_type' => 'quarter',                       // used when querying the remote data
            ],
    ],
    'resolution_names'=> [  60      => '1min',
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
