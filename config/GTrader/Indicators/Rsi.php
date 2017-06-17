<?php

return [
    'indicator' =>  [
        'base' => 'close',
        'period' => 14,
    ],
    'adjustable' => [
        'base' => [
            'name' => 'Base',
            'type' => 'base',
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
        'top_level' => true,
    ],
    'fill_value' => 50,
    'normalize_type' => 'range',
    'range' => [
        'min' => 0,
        'max' => 100,
    ],
];
