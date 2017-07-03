<?php

return [
    'indicator' =>  [
        'mode' => 'dx',
        'input_high' => 'high',
        'input_low' => 'low',
        'input_close' => 'close',
        'period' => 25,
    ],
    'adjustable' => [
        'mode' => [
            'name' => 'Mode',
            'type' => 'select',
            'options' => ['dx' => 'DX', 'adx' => 'ADX', 'adxr' => 'ADXR'],
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
        'name' => 'DMI',
        'description' => 'Directional Movement Index by J. Welles Wilder',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'outputs' => ['DX', 'Plus', 'Minus'],
    'fill_value' => null,
    'normalize' => [
        'mode' => 'range',
        'range' =>  [
            'min' => 0,
            'max' => 100,
        ],
    ],
];
