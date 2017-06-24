<?php

return [
    'indicator' =>  [
        'input_high' => 'high',
        'input_low' => 'low',
        'input_close' => 'close',
        'input_volume' => 'volume',
        'slowperiod' => 10,
        'fastperiod' => 3,
    ],
    'adjustable' => [
        'slowperiod' => [
            'name' => 'Slow Period',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 99,
        ],
        'fastperiod' => [
            'name' => 'Fast Period',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 99,
        ],
    ],
    'display' => [
        'name' => 'A/D Oscillator',
        'description' => 'Chaikin Accumulation/Distribution Oscillator',
        'y_axis_pos' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 0,
    'normalize_type' => 'individual',
    'normalize_to' => 0,
];
