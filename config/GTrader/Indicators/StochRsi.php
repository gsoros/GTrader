<?php

return [
    'indicator' =>  [
        'input_source' => 'close',
        'period' => 14,
        'fastk' => 14,
        'fastd' => 3,
        'matype' => TRADER_MA_TYPE_SMA,
    ],
    'adjustable' => [
        'input_source' => [
            'name' => 'Source',
            'type' => 'source',
        ],
        'period' => [
            'name' => 'Period',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 99,
        ],
        'fastk' => [
            'name' => 'FastK Period',
            'type' => 'int',
            'min' => 1,
            'step' => 1,
            'max' => 99,
        ],
        'fastd' => [
            'name' => 'FastD Period',
            'type' => 'int',
            'min' => 1,
            'step' => 1,
            'max' => 99,
        ],
        'matype' => [
            'name' => 'FastD MA Type',
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
        'name' => 'StochRSI',
        'description' => 'Stochastic Relative Strength Index',
        'y_axis_pos' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 50,
];
