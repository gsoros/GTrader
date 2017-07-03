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
        'mode' => [
            'name' => 'Mode',
            'type' => 'select',
            'options' => [
                'candlestick' => 'Candlesticks',
                'ha' => 'Heikin Ashi',
                'linepoints' => 'Line',
            ],
        ],
    ],
    'outputs' => ['open', 'high', 'low', 'close'],
    'display' => [
        'mode' => 'candlestick',
        'name' => 'OHLC',
        'description' => 'Open, High, Low, Close',
        'y_axis_pos' => 'left',
        'index' => -1,
    ],
    'normalize' => [
        'mode' => 'ohlc',
    ],
];
