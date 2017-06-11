<?php

return [
    'indicator' => [
        'mode' => 'dynamic',
        'capital' => 100,
    ],
    'adjustable' => [
        'mode' => [
            'name' => 'Mode',
            'type' => 'select',
            'options' => [
                'fixed' => 'Fixed',
                'dynamic' => 'Dynamic',
            ],
        ],
        'capital' => [
            'name' => 'Initial Capital',
            'type' => 'number',
            'min' => 1,
            'step' => 1,
            'max' => 1000,
        ],
    ],
    'display' => [
        'name' => 'Balance',
        'description' => 'Balance',
        'y_axis_pos' => 'right',
    ],
];
