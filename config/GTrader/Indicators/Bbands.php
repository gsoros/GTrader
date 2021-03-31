<?php

return [
    'indicator' =>  [
        'input_source' => 'close',
        'period' => 30,
        'devup' => 2,
        'devdown' => 2,
        'matype' => TRADER_MA_TYPE_SMA,
    ],
    'adjustable' => [
        'input_source' => [
            'name' => 'Source',
            'type' => 'source',
        ],
        'period' => [
            'name' => 'Period',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 99,
        ],
        'devup' => [
            'name' => 'Dev Upper',
            'description' => 'Deviation multiplier for upper band',
            'type' => 'float',
            'min' => 0,
            'step' => .05,
            'max' => 10,
        ],
        'devdown' => [
            'name' => 'Dev Lower',
            'description' => 'Deviation multiplier for lower band',
            'type' => 'float',
            'min' => 0,
            'step' => .05,
            'max' => 10,
        ],
        'matype' => [
            'name' => 'MA Type',
            'type' => 'select',
        ],
    ],
    'outputs' => ['Upper', 'Middle', 'Lower'],
    'display' => [
        'name' => 'BBands',
        'description' => 'Bollinger Bands',
        'y-axis' => 'left',
        'top_level' => false,
    ],
    'fill_value' => null,
];
