<?php

return [
    'indicator' =>  [
        'fastkperiod' => 14,
        'slowkperiod' => 3,
        'slowkmatype' => TRADER_MA_TYPE_SMA,
        'slowdperiod' => 14,
        'slowdmatype' => TRADER_MA_TYPE_SMA,

    ],
    'adjustable' => [
        'fastkperiod' => [
            'name' => 'FastK Period',
            'type' => 'number',
            'min' => 1,
            'step' => 1,
            'max' => 99,
        ],
        'slowkperiod' => [
            'name' => 'SlowK Period',
            'type' => 'number',
            'min' => 1,
            'step' => 1,
            'max' => 99,
        ],
        'slowkmatype' => [
            'name' => 'SlowK MA Type',
            'type' => 'select',
        ],
        'slowdperiod' => [
            'name' => 'SlowD Period',
            'type' => 'number',
            'min' => 1,
            'step' => 1,
            'max' => 99,
        ],
        'slowdmatype' => [
            'name' => 'SlowD MA Type',
            'type' => 'select',
        ],
    ],
    'outputs' => ['K', 'D'],
    'normalize_type' => 'range',
    'range' => [
        'min' => 0,
        'max' => 100,
    ],
    'display' => [
        'name' => 'Stoch',
        'description' => 'Stochastic',
        'y_axis_pos' => 'right',
        'top_level' => true,
    ],
    'fill_value' => 50,
];
