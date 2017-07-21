<?php

return [
    'indicator' =>  [
        'input_high' => 'high',
        'input_low' => 'low',
        'input_close' => 'close',
        'period' => 20,
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
        'period' => [
            'name' => 'Period',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 99,
        ],
    ],
    'display' => [
        'name' => 'CCI',
        'description' => 'Commodity Channel Index by Donald Lambert',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 0,
    'normalize' => [
        'mode' => 'individual',
        'to' => 0,
    ],
];
