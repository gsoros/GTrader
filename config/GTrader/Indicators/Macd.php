<?php

return [
    'indicator' =>  [
        'input_source' => 'close',
        'fastperiod' => 12,
        'slowperiod' => 26,
        'signalperiod' => 9,
        'fastmatype' => TRADER_MA_TYPE_EMA,
        'slowmatype' => TRADER_MA_TYPE_EMA,
        'signalmatype' => TRADER_MA_TYPE_EMA,
    ],
    'adjustable' => [
        'input_source' => [
            'name' => 'Source',
            'type' => 'source',
        ],
        'fastperiod' => [
            'name' => 'Fast Period',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 99,
        ],
        'slowperiod' => [
            'name' => 'Slow Period',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 99,
        ],
        'signalperiod' => [
            'name' => 'Signal Period',
            'type' => 'int',
            'min' => 1,
            'step' => 1,
            'max' => 99,
        ],
        'fastmatype' => [
            'name' => 'Fast MA Type',
            'type' => 'select',
        ],
        'slowmatype' => [
            'name' => 'Slow MA Type',
            'type' => 'select',
        ],
        'signalmatype' => [
            'name' => 'Signal MA Type',
            'type' => 'select',
        ],
    ],
    'outputs' => ['Fast', 'Slow', 'Signal'],
    'normalize_type' => 'individual',
    'normalize_to' => 0,
    'display' => [
        'name' => 'MACD',
        'description' => 'Moving Average Convergence/Divergence',
        'y_axis_pos' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 0,
];
