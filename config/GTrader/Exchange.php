<?php

return [

    'children_ns'       => 'Exchanges',
    'default_child'     => env('EXCHANGE_DEFAULT',         'OKCoin_Futures'),
    'resolution'        => env('EXCHANGE_RESOLUTION',       60),   // default resolution
    'size'              => env('EXCHANGE_SIZE',             180),  // default # of candles to display
    'position_size'     => env('EXCHANGE_POSITION_SIZE',    0.30), // 30% of capital
    'leverage'          => env('EXCHANGE_LEVERAGE',         20),
    'fee_multiplier'    => env('EXCHANGE_FEE_MULTIPLIER',   0.005),
];
