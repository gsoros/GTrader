<?php

return [
    'indicator' =>  [
        'input_open' => 'open',
        'input_high' => 'high',
        'input_low' => 'low',
        'input_close' => 'close',
    ],
    'adjustable' => [
    ],
    'display' => [
        'name' => 'BOP',
        'description' => 'Balance Of Power',
        'y_axis_pos' => 'right',
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
