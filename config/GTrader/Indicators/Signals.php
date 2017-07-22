<?php

return [
    'indicator' => [
        'strategy_id' => -1,
        'input_long_a' => 'open',
        'long_cond' => '>',
        'input_long_b' => 'open',
        'input_long_source' => 'open',
        'input_short_a' => 'open',
        'short_cond' => '<',
        'input_short_b' => 'open',
        'input_short_source' => 'open',
    ],
    'adjustable' => [
        'strategy_id' => [
            'name' => 'Strategy',
            'type' => 'select',
            'options' => [
                -1 => 'Automatic From Parent',
                0 => 'Custom Settings',
            ],
        ],
        'input_long_a' => [
            'name' => 'Long A',
            'type' => 'source',
        ],
        'long_cond' => [
            'name' => 'Long Condition',
            'type' => 'select',
            'options' => [
                '<' => 'LT',
                '>' => 'GT',
            ],
        ],
        'input_long_b' => [
            'name' => 'Long B',
            'type' => 'source',
        ],
        'input_long_source' => [
            'name' => 'Long Source',
            'type' => 'source',
        ],
        'input_short_a' => [
            'name' => 'Short A',
            'type' => 'source',
        ],
        'short_cond' => [
            'name' => 'Short Condition',
            'type' => 'select',
            'options' => [
                '<' => 'LT',
                '>' => 'GT',
            ],
        ],
        'input_short_b' => [
            'name' => 'Short B',
            'type' => 'source',
        ],
        'input_short_source' => [
            'name' => 'Short Source',
            'type' => 'source',
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
