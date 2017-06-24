<?php

return [
    'indicator' =>  [
        'input_high' => 'high',
        'input_low' => 'low',
        'input_close' => 'close',
        'period' => 20,
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
        'name' => 'CCI',
        'description' => 'Commodity Channel Index by Donald Lambert',
        'y_axis_pos' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 0,
    'normalize_type' => 'individual',
    'normalize_to' => 0,
];
