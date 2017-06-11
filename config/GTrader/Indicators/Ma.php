<?php

return [
    'indicator' => [
        'base' => 'close',
        'length' => 20,
        'type' => TRADER_MA_TYPE_SMA,
    ],
    'adjustable' => [
        'base' => [
            'name' => 'Base',
            'type' => 'base',
        ],
        'length' => [
            'name' => 'Length',
            'type' => 'number',
            'min' => 2,
            'step' => 1,
            'max' => 1000,
        ],
        'type' => [
            'name' => 'Type',
            'type' => 'select',
            'options' => TRADER_MA_TYPES,
        ],
    ],
    'display' => [
        'name' => 'MA',
        'description' => 'Moving Average',
        'y_axis_pos' => 'right',
    ],

];
