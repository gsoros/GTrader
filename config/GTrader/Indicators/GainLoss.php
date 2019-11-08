<?php

return [
    'indicator' => [
        'mode' => 'loss',
        'maximum' => false,
        'input_a' => 'open',
    ],
    'adjustable' => [
        'mode' => [
            'name' => 'Mode',
            'type' => 'select',
            'options' => [
                'gain' => 'Gain',
                'loss' => 'Loss',
            ],
        ],
        'maximum' => [
            'name' => 'Maximum',
            'type' => 'bool',
        ],
        'input_a' => [
            'name' => 'Source',
            'type' => 'source',
        ],
    ],
    'display' => [
        'name' => 'Gain/Loss',
        'description' => '(Maximum) Gain or Loss, output is in percentage',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'normalize' => [
        'mode' => 'individual',
    ],
];
