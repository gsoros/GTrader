<?php

return [
    'indicator' => [
        'input_source' => 'volume',
    ],
    'display' => [
        'mode' => 'bars',
        'name' => 'Volume',
        'description' => 'Displays the trading volume',
        'y-axis' => 'right',
        'z-index' => -2, // below ohlc
    ],
    'outputs' => ['volume'],
    'normalize' => [
        'mode' => 'individual',
        'to' => 0,
    ],
];
