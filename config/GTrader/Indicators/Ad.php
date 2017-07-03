<?php

return [
    'indicator' =>  [
        'input_high' => 'high',
        'input_low' => 'low',
        'input_close' => 'close',
        'input_volume' => 'volume',
    ],
    'adjustable' => [
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
