<?php

return [
    'indicator' =>  [
        'input_high' => 'high',
        'input_low' => 'low',
        'period' => 25,
    ],
    'adjustable' => [
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
        'y_axis_pos' => 'right',
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
