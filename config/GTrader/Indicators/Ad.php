<?php

return [
    'indicator' =>  [
        'input_high' => 'high',
        'input_low' => 'low',
        'input_close' => 'close',
        'input_volume' => 'volume',
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
    ],
    'display' => [
        'name' => 'A/D Line',
        'description' => 'Chaikin Accumulation/Distribution Line',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 0,
    'normalize' => [
        'mode' => 'individual',
        'to' => 0,
    ],
];
