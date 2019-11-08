<?php

return [
    'indicator' => [
        'operation' => 'max',
        'input_a' => 'open',
    ],
    'adjustable' => [
        'operation' => [
            'name' => 'Operation',
            'type' => 'select',
            'options' => [
                'min' => 'Min',
                'max' => 'Max',
            ],
        ],
        'input_a' => [
            'name' => 'Source',
            'type' => 'source',
        ],
    ],
    'display' => [
        'name' => 'MinMax',
        'description' => 'Minimum or maximum',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'normalize' => [
        'mode' => 'individual',
    ],
];
