<?php

return [
    'indicator' => [
        'input_signal' => '', // set in createDependencies()
    ],
    'adjustable' => [
        'input_signal' => [
            'name' => 'Signal',
            'type' => 'source',
            'filters' => [
                'class' => 'Signals',
            ],
            'disabled' => [
                'Constant',
                'outputs',
            ],
        ],
    ],
    'normalize' => [
        'mode' => 'individual',
    ],
    'display' => [
        'name' => 'Winners vs. losers',
        'description' => 'Displays the ratio of winning and losing trades',
        'y-axis' => 'right',
    ],
];
