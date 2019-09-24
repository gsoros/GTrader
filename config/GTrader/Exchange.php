<?php

return [

    'schedule_frequency' => env('EXCHANGE_SCHEDULE_FREQ', 13),              // Fetch new candles once every # of minutes
    'children_ns'           => 'Exchanges',
    'default_child'         => env('EXCHANGE_DEFAULT', 'OKEX_BTC_Futures'),
    'available_exchanges'   => [                                            // list of installed exchange classes
        'OKEX_BTC_Futures',
        //'OKCoin_ETH_Spot',
        //'OKEX_BCC_Spot',
    ],

    'user_options'          => [],                                          // User-configurable options, to be overridden in children

    'fee_multiplier'    => env('EXCHANGE_FEE_MULTIPLIER', 0.01),           // TODO make this configurable in UserExConf
    'position_size'     => env('EXCHANGE_POSITION_SIZE', 10),               // 10% of capital
    'leverage'          => env('EXCHANGE_LEVERAGE', 10),

    'delete_candle_age' => env('DELETE_CANDLE_AGE', 0),                     // delete candles older than this # of days, 0 to disable
    'aggregator_delay'  => env('AGGREGATOR_DELAY', 2000000),                // sleep this # of microseconds between requests

];
