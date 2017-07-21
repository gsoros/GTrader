<?php

return [
    'indicator' =>  [
        'input_open' => 'open',
        'input_high' => 'high',
        'input_low' => 'low',
        'input_close' => 'close',
    ],
    'adjustable' => [
        'input_open' => [
            'name' => 'Open Source',
            'type' => 'source',
        ],
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
    ],
    'display' => [
        'name' => 'BOP',
        'description' => 'Balance Of Power',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 0,
    'normalize' => [
        'mode' => 'range',
        'range' =>  [
            'min' => -1,
            'max' => 1,
        ],
    ],
];
