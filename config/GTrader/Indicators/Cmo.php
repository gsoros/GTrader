<?php

return [
    'indicator' =>  [
        'input_source' => 'open',
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
        'name' => 'CMO',
        'description' => 'Chande Momentum Oscillator',
        'y_axis_pos' => 'right',
        'top_level' => false,
    ],
    'fill_value' => null,
    'normalize' => [
        'mode' => 'range',
        'range' => [
            'min' => -100,
            'max' => 100,
        ],
    ],
];
