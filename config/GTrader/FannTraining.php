<?php

return [
    'max_time_per_session'  => 183,       // number of seconds before giving slot to others
    'train_range'           => ['start_percent' => 0,   'end_percent' => 30],
    'test_range'            => ['start_percent' => 30,  'end_percent' => 60],
    'verify_range'          => ['start_percent' => 60,  'end_percent' => 80],
    'crosstrain'            => 0,
    'reset_after'           => 0,
    'maximize_for'          => 'Balance Fixed',
    'suffix'                => '.train',
    'max_boredom'           => 10,   // increase jump size after this number of uneventful epochs
    'epoch_jump_max'        => 100,  // max amount of epochs between tests
    'test_regression'       => .9,   // allow this amount of regression to test max
    'indicators'            => [
        [
            'name' => 'Balance Fixed',
            'class' => 'Balance',
            'params' => [
                'indicator' => ['mode' => 'fixed'],
            ],
        ],
        [
            'name' => 'Balance Dynamic',
            'class' => 'Balance',
            'params' => [
                'indicator' => ['mode' => 'dynamic'],
            ],
        ],
        [
            'name' => 'Winners vs. losers',
            'class' => 'Profitability',
            'params' => [],
        ],
        [
            'name' => 'Average Balance',
            'class' => 'Avg',
            'params' => [
                'indicator' => ['input_source' => '{"class":"Balance","params":{"mode":"fixed","capital":100}}'],
            ],
        ],
    ],
    /* Default indicator to maximise training on */
    'indicator'           => [
        'name' => 'Winners vs. losers',
        'class' => 'Profitability',
        'params' => [],
    ],
];
