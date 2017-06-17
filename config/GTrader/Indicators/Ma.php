<?php

return [
    'indicator' => [
        'base' => 'close',
        'length' => 20,
        'type' => TRADER_MA_TYPE_EMA,
    ],
    'adjustable' => [
        'base' => [
            'name' => 'Base',
            'type' => 'base',
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
        'y_axis_pos' => 'right',
    ],

];
