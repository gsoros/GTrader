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
                'ohlc' => 'OHLC',
                'ha' => 'Heikin Ashi',
                'linepoints' => 'Line',
            ],
            'immutable' => true,
        ],
    ],
    'outputs' => ['open', 'high', 'low', 'close'],
    'display' => [
        'mode' => 'candlestick',
        'name' => 'Candles',
        'description' => 'Open, High, Low, Close',
        'y-axis' => 'left',
        'z-index' => -1, // below indicators
        'editable_outputs' => ['none'],
    ],
    'normalize' => [
        'mode' => 'ohlc',
    ],
];
