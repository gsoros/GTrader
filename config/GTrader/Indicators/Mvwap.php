<?php

return [
    'indicator' =>  [
        'input_source' => 'open',
        'period' => 20,
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
    ],
    'display' => [
        'name' => 'MVWAP',
        'description' => 'Moving Volume Weighted Average Price',
        'y-axis' => 'left',
        'top_level' => false,
    ],
    'normalize' => [
        'mode' => 'ohlc',
    ],
];
