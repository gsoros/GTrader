<?php

return [
    'indicator' => [
        'input_source' => 'volume',
    ],
    'display' => [
        'mode' => 'bars',
        'name' => 'Volume',
        'description' => 'Displays the trading volume',
        'y_axis_pos' => 'right',
        'index' => -2,
    ],
    'normalize' => [
        'mode' => 'individual',
        'to' => 0,
    ],
];
