<?php

return [
    /* Order statuscodes */
    'order_statuscodes' => [
        -1 => 'cancelled',
        0 => 'unfilled',
        1 => 'partially_filled',
        2 => 'filled',
        4 => 'cancel_in_progress'
    ],

    /* Order type codes */
    'order_types' => [
        1 => 'open_long',
        2 => 'open_short',
        3 => 'close_long',
        4 => 'close_short'
    ],

    /* OKCoin-specific resolution strings */
    'resolution_names' => [
        60      => '1min',
        180     => '3min',      //       3*60
        300     => '5min',      //       5*60
        900     => '15min',     //      15*60
        1800    => '30min',     //      30*60
        3600    => '1hour',     //      60*60
        7200    => '2hour',     //    2*60*60
        14400   => '4hour',     //    4*60*60
        21600   => '6hour',     //    6*60*60
        43200   => '12hour',    //   12*60*60
        86400   => '1day',      //   24*60*60
        259200  => '3day',      // 3*24*60*60
        604800  => '1week'      // 7*24*60*60
    ],

];
