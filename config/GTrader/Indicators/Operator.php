<?php

return [
    'indicator' => [
        'input_a' => 'open',
        'operation' => 'add',
        'input_b' => 'close',
    ],
    'adjustable' => [
        'input_a' => [
            'name' => 'Source A',
            'type' => 'source',
        ],
        'operation' => [
            'name' => 'Operation',
            'type' => 'select',
            'options' => [
                'add' => 'Add',
                'sub' => 'Subtract',
                'mult' => 'Multiply',
                'div' => 'Divide',
                'perc' => 'Percentage',
            ],
        ],
        'input_b' => [
            'name' => 'Source B',
            'type' => 'source',
        ],
    ],
    'display' => [
        'name' => 'Operator',
        'description' => 'Simple Mathematical Operations',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'normalize' => [
        'mode' => 'individual',
    ],
];
