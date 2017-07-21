<?php

return [
    'indicator' =>  [
        'input_high' => 'high',
        'input_low' => 'low',
        'input_close' => 'close',
        'input_volume' => 'volume',
        'slowperiod' => 10,
        'fastperiod' => 3,
    ],
    'adjustable' => [
        'input_high' => [
            'name' => 'High Source',
            'type' => 'source',
        ],
        'input_low' => [
            'name' => 'Low Source',
            'type' => 'source',
        ],
        'input_close' => [
            'name' => 'Close Source',
            'type' => 'source',
        ],
        'input_volume' => [
            'name' => 'Volume Source',
            'type' => 'source',
        ],
        'slowperiod' => [
            'name' => 'Slow Period',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 99,
        ],
        'fastperiod' => [
            'name' => 'Fast Period',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 99,
        ],
    ],
    'display' => [
        'name' => 'A/D Oscillator',
        'description' => 'Chaikin Accumulation/Distribution Oscillator',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 0,
    'normalize' => [
        'mode' => 'individual',
        'normalize_to' => 0,
    ],
];
