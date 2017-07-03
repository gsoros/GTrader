<?php

return [
    'indicator' => [
        'mode' => 'fixed',
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
            'type' => 'int',
            'min' => 1,
            'step' => 1,
            'max' => 1000,
        ],
    ],
    'display' => [
        'name' => 'Balance',
        'description' => 'Balance',
        'y-axis' => 'right',
    ],
    'normalize' => [
        'mode' => 'individual',
    ],
];
