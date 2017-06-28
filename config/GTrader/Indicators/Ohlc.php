<?php

return [
    'indicator' => [
        'input_open' => 'open',
        'input_high' => 'high',
        'input_low' => 'low',
        'input_close' => 'close',
        'mode' => 'candlestick',
    ],
    'adjustable' => [
        'input_open' => [
            'name' => 'Open Source',
            'type' => 'source',
        ],
        'input_high' => [
            'name' => 'High Source',
            'type' => 'source',
        ],
        'input_low' => [
            'name' => 'Low Source',
            'type' => 'source',
        ],
        'input_close' => [
            'name' => 'Close Source',
            'type' => 'source',
        ],
        'mode' => [
            'name' => 'Mode',
            'type' => 'select',
            'options' => [
                'candlestick' => 'Candlestick',
                'ha' => 'Heikin Ashi',
            ],
        ],
    ],
    'outputs' => ['Open', 'High', 'Low', 'Close'],
    'display' => [
        'mode' => 'candlestick',
        'name' => 'OHLC',
        'description' => 'Open, High, Low, Close',
        'y_axis_pos' => 'left',
    ],
];
