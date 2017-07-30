<?php

return [
    'indicator' => [
        'mode' => 'fixed',
        'capital' => 100,
        'input_signal' => '', // set in createDependencies()
    ],
    'adjustable' => [
        'mode' => [
            'name' => 'Mode',
            'description' =>
                'Fixed mode invests a fixed percentage of the starting balance. '.
                'Dynamic mode adjusts the invested amount after each trade; '.
                'this can result in exponential growth and never losing the full balance. '.
                'The percentage can be set in the exchange settings.',
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
    'display' => [
        'name' => 'Balance',
        'description' => 'Calculates Balance',
        'y-axis' => 'right',
    ],
    'normalize' => [
        'mode' => 'individual',
    ],
];
