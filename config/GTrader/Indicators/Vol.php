<?php

return [
    'indicator' => [
        'input_source' => 'volume',
    ],
    'adjustable' => [
        'input_source' => [
            'name' => 'Source',
            'type' => 'source',
        ],
    ],
    'display' => [
        'mode' => 'bars',
        'name' => 'Volume',
        'description' => 'Displays the trading volume',
        'y_axis_pos' => 'right',
    ],
    'normalize' => [
        'mode' => 'individual',
        'to' => 0,
    ],
];
