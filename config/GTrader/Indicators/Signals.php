<?php

return [
    'indicator' => [
        'strategy_id' => -1,
        'input_open_long_a' => 'open',
        'open_long_cond' => '>',
        'input_open_long_b' => 'open',
        'input_open_long_source' => 'open',
        'input_open_short_a' => 'open',
        'open_short_cond' => '<',
        'input_open_short_b' => 'open',
        'input_open_short_source' => 'open',
        'min_trade_distance' => 1,
    ],
    'adjustable' => [
        'strategy_id' => [
            'name' => 'Strategy',
            'type' => 'select',
            'options' => [
                -1 => 'Automatic From Parent',
                0 => 'Custom Settings',
            ],
            'immutable' => true,                // do not mutate this setting
        ],
        'input_open_long_a' => [
            'name' => 'Open Long A',
            'type' => 'source',
            'display' => [
                'hide' => ['label'],
                'group' => [
                    'label' => 'Open Long',
                    'description' => 'Generate an \'Open Long\' signal by comparing two sources',
                    'cols' => 4,
                ],
            ],
        ],
        'open_long_cond' => [
            'name' => 'Open Long Condition',
            'type' => 'select',
            'options' => [
                '<' => '<',
                '<=' => '<=',
                '>' => '>',
                '>=' => '>=',
            ],
            'display' => [
                'hide' => ['label'],
                'group' => [
                    'label' => 'Open Long',
                    'cols' => 1,
                ],
            ],
        ],
        'input_open_long_b' => [
            'name' => 'Open Long B',
            'type' => 'source',
            'display' => [
                'hide' => ['label'],
                'group' => [
                    'label' => 'Open Long',
                    'cols' => 4,
                ],
            ],
        ],
        'input_open_long_source' => [
            'name' => 'Open Long Source',
            'type' => 'source',
            'immutable' => true,                // do not mutate this setting
            'description' => 'Source for the \'Open Long\' signal price. Used in back-testing and if the exchange is configured to use limit orders.',
        ],
        'input_open_short_a' => [
            'name' => 'Open Short A',
            'type' => 'source',
            'display' => [
                'hide' => ['label'],
                'group' => [
                    'label' => 'Open Short',
                    'description' => 'Generate an \'Open Short\' signal by comparing two sources',
                    'cols' => 4,
                ],
            ],
        ],
        'open_short_cond' => [
            'name' => 'Open Short Condition',
            'type' => 'select',
            'options' => [
                '<' => '<',
                '<=' => '<=',
                '>' => '>',
                '>=' => '>=',
            ],
            'display' => [
                'hide' => ['label'],
                'group' => [
                    'label' => 'Open Short',
                    'cols' => 1,
                ],
            ],
        ],
        'input_open_short_b' => [
            'name' => 'Open Short B',
            'type' => 'source',
            'display' => [
                'hide' => ['label'],
                'group' => [
                    'label' => 'Open Short',
                    'cols' => 4,
                ],
            ],
        ],
        'input_open_short_source' => [
            'name' => 'Open Short Source',
            'type' => 'source',
            'immutable' => true,                // do not mutate this setting
            'description' => 'Source for the \'Open Short\' signal price. Used in back-testing and if the exchange is configured to use limit orders.',
        ],
        'min_trade_distance' => [
            'name' => 'Minimum Trade Distance',
            'type' => 'int',
            'min' => 1,
            'max' => 100,
        ],
    ],
    'display' =>  [
        'name' => 'Signals',
        'mode' => 'linepoints',
        'top_level' => false,
        'y-axis' => 'left',
    ],
    'outputs' => [
        'signal',
        'price',
    ],
];
