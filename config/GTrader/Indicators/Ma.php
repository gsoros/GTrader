<?php

return [
    'indicator' => [
        'input_source' => 'close',
        'length' => 20,
        'type' => TRADER_MA_TYPE_EMA,
    ],
    'adjustable' => [
        'input_source' => [
            'name' => 'Source',
            'type' => 'source',
        ],
        'length' => [
            'name' => 'Length',
            'type' => 'int',
            'min' => 2,
            'step' => 1,
            'max' => 1000,
        ],
        'type' => [
            'name' => 'Type',
            'type' => 'select',
        ],
    ],
    'display' => [
        'name' => 'MA',
        'description' => 'Moving Average',
        'y-axis' => 'right',
        'auto-y-axis' => true,
    ],

];
