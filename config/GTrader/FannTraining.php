<?php

return [
    'max_time_per_session'  => 183,       // number of seconds before giving slot to others
    'train_range'           => ['start_percent' => 0,   'end_percent' => 30],
    'test_range'            => ['start_percent' => 30,  'end_percent' => 60],
    'verify_range'          => ['start_percent' => 60,  'end_percent' => 80],
    'suffix'                => '.train',
    'max_boredom'           => 10,   // increase jump size after this many checks without improvement
    'epoch_jump_max'        => 100,  // max amount of epochs between tests
    'test_regression'       => .9,   // allow this amount of regression to test max
    'indicator'             => 'Balance',
    //'indicator'           => 'Profitability',
    'indicator_params'      => [],
    //'indicator'            = 'Avg',
    //'indicator_params'    = ['indicator' => ['base' => 'Balance_mode_fixed_capital_100']],
];
