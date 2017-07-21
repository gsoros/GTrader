<?php

return [
    'indicator' =>  [
        'input_high' => 'high',
        'input_low' => 'low',
        'period' => 25,
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
        'period' => [
            'name' => 'Period',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 99,
        ],
    ],
    'display' => [
        'name' => 'Aroon Oscillator',
        'description' => 'Aroon Oscillator by Tushar Chande',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 0,
    'normalize' => [
        'mode' => 'range',
        'range' =>  [
            'min' => -100,
            'max' => 100,
        ],
    ],
];
