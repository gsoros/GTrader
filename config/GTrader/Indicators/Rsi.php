<?php

return [
    'indicator' =>  [
        'input_source' => 'close',
        'period' => 14,
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
    ],
    'display' => [
        'name' => 'RSI',
        'description' => 'Relative Strength Index',
        'y_axis_pos' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 50,
    'normalize' => [
        'mode' => 'range',
        'range' => [
            'min' => 0,
            'max' => 100,
        ],
    ],
];
