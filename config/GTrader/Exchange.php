<?php

return [

    'schedule_frequency' => env('EXCHANGE_SCHEDULE_FREQ', 1),               // Fetch new candles once every minute
    'children_ns'           => 'Exchanges',
    'default_child'         => env('EXCHANGE_DEFAULT', 'OKEX_BTC_Futures'),
    'available_exchanges'   => [                                            // list of installed exchange classes
        'OKEX_BTC_Futures',
        //'OKCoin_ETH_Spot',
        //'OKEX_BCC_Spot',
    ],

    'user_options'          => [],                                          // User-configurable options, to be overridden in children

    'fee_multiplier'    => env('EXCHANGE_FEE_MULTIPLIER', 0.005),           // TODO make this configurable in UserExConf
    'position_size'     => env('EXCHANGE_POSITION_SIZE', 30),               // 30% of capital
    'leverage'          => env('EXCHANGE_LEVERAGE', 10),

];
