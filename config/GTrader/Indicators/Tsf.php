<?php

return [
    'indicator' =>  [
        'input_source' => 'open',
        'period' => 10,
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
        'name' => 'TSF',
        'description' => 'Time Series Forecast',
        'y_axis_pos' => 'left',
        'top_level' => false,
    ],
    'fill_value' => 'input_source',
    'normalize_type' => 'ohlc',
];
