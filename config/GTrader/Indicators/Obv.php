<?php

return [
    'indicator' =>  [
        'input_source' => 'close',
    ],
    'adjustable' => [
        'input_source' => [
            'name' => 'Source',
            'type' => 'source',
        ],
    ],
    'display' => [
        'name' => 'OBV',
        'description' => 'On Balance Volume',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'fill_value' => null,
    'normalize' => [
        'mode' => 'individual',
    ],
];
