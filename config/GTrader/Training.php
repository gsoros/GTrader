<?php

return [
    'max_time_per_session'  => 183,             // number of seconds before giving slot to others
    'maximize'              => [
        'balance_fixed' => 'Balance Fixed',
        'balance_dynamic' => 'Balance Dynamic',
        'profitability' => 'Winners vs. losers',
        'avg_balance' => 'Average Balance',
    ],
    'ranges' => [
        'train' => [
            'start_percent' => 0,
            'end_percent' => 30
        ],
        'test' => [
            'start_percent' => 30,
            'end_percent' => 60
        ],
        'verify' => [
            'start_percent' => 60,
            'end_percent' => 80
        ],
    ],
];
