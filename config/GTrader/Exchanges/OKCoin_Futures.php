<?php

return [

    'name_sql'      => env('OKCOIN_FUTURES_NAME_SQL',       'OKCoin_Futures'),
    'contract_type' => env('OKCOIN_FUTURES_CONTRACT_TYPE',  'quarter'),
    'symbol'        => env('OKCOIN_FUTURES_SYMBOL',         'btc_usd'),         // symbol sent in the query to okcoin
    'symbol_sql'    => env('OKCOIN_FUTURES_SYMBOL_SQL',     'btc_usd_3m'),      // symbol stored in our db
    'resolutions'   => [  60      => '1min',
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
                          604800  => '1week']  // 7*24*60*60

];
