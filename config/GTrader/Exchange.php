<?php

return [

    'schedule_frequency' => env('EXCHANGE_SCHEDULE_FREQ', 3),               // Fetch new candles once every # of minutes
    'children_ns'           => 'Exchanges',
    'default_child'         => env('EXCHANGE_DEFAULT', 'CCXT'),
    'available_exchanges'   => [                                            // list of installed exchange classes
        //'OKEX_BTC_Futures',
        'CCXT',
    ],

    'user_options'          => [],                                          // User-configurable options, to be overridden in children

    'fee_multiplier'    => env('EXCHANGE_FEE_MULTIPLIER', 0.005),           // TODO make this configurable in UserExConf
    'position_size'     => env('EXCHANGE_POSITION_SIZE', 10),               // 10% of capital
    'leverage'          => env('EXCHANGE_LEVERAGE', 10),

    'delete_candle_age'         => env('DELETE_CANDLE_AGE', 0),             // delete candles older than this # of days, 0 to disable
    'aggregator_delay'          => env('AGGREGATOR_DELAY', 500000),         // sleep this # of microseconds between requests
    'aggregator_chunk_size'     => env('AGGREGATOR_CHUNK_SIZE', 10),      // # of candles to fetch at a time

    'resolution_map'    => [
        '1m'    =>  60,
        '3m'    =>  180,
        '5m'    =>  300,
        '10m'   =>  600,
        '15m'   =>  900,
        '20m'   =>  1200,
        '30m'   =>  1800,
        '45m'   =>  2700,
        '1h'    =>  3600,
        '2h'    =>  7200,
        '3h'    =>  10800,
        '4h'    =>  14400,
        '6h'    =>  21600,
        '8h'    =>  28800,
        '12h'   =>  43200,
        '1d'    =>  86400,
        '3d'    =>  259200,
        '5d'    =>  432000,
        '1w'    =>  604800,
        '2w'    =>  1209600,
        '1M'    =>  2592000,
        '1y'    =>  31536000,
        /*
        "1d":["1day","D1","day","1d","1D","D",86400,"d","day1","days"],
        "1m":["1","1min","M1","1m","oneMin","minute",60,"m","minute1","min1","minutes"],
        "5m":["5","5min","M5","5m","fiveMin",300,"minute5","min5","minutes"],
        "15m":["15","15min","M15","15m",900,"minute15","min15","minutes"],
        "30m":["30","30min","M30","30m","thirtyMin","1800","minute30","min30","minutes"],
        "1h":["60","1hour","H1","1h",3600,"hour","60min","h","1hr","hour1","hr1","minutes"],
        "2h":["120","2hour","2h","7200","hour2"],
        "4h":["240","4hour","H4","4h","14400","hour4","minutes"],
        "12h":["720","12hour","12h","43200","hour12"],"3d":["4320","3day","3d"],
        "1w":["10080","1week","D7","week","1w","7D","W","1W","W1","604800","week1","weeks"],
        "3m":["3min","M3","3m","180","minutes"],
        "6h":["6hour","6h",21600,"6hr","hour6"],
        "1M":["1M",43200,"1mon","1mo","MN","1month","months"],
        "8h":["8h","8hour","hour8"],
        "3h":["3h",180],
        "2w":["14D","21600","2w"],
        "45m":[45],
        "5d":["5day"],
        "20m":["20m"],
        "1y":["1year"],
        "10m":["600"]
        */
    ],
];
