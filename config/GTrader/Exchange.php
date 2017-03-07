<?php

return [

    'children_ns'           => 'Exchanges',
    'default_child'         => env('EXCHANGE_DEFAULT', 'OKCoin_Futures'),
    'available_exchanges'   => ['OKCoin_Futures', 'Dummy'],                 // list of installed exchange classes

    'user_options'          => [],                                          // User-configurable options, to be overridden in children

    'fee_multiplier'    => env('EXCHANGE_FEE_MULTIPLIER',   0.005),         // TODO make this configurable in UserExConf
    'position_size'     => env('EXCHANGE_POSITION_SIZE',    30),            // 30% of capital
    'leverage'          => env('EXCHANGE_LEVERAGE',         10),

];
