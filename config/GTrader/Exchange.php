<?php

return [

    'children_ns'           => 'Exchanges',
    'default_child'         => env('EXCHANGE_DEFAULT', 'OKCoin_Futures'),
    'available_exchanges'   => ['OKCoin_Futures'],                          // list of installed exchange classes

    'position_size'     => env('EXCHANGE_POSITION_SIZE',    0.30), // 30% of capital
    'leverage'          => env('EXCHANGE_LEVERAGE',         20),
    'fee_multiplier'    => env('EXCHANGE_FEE_MULTIPLIER',   0.005),
];
