<?php

return [
    'indicator' => [
        'input_a' => 'close',
        'operation' => 'gt',
        'input_b' => 'open',
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
                'eq'    => '=',
                'lt'    => '<',
                'lte'   => '<=',
                'gt'    => '>',
                'gte'   => '>=',
                'and'   => 'AND',
                'or'    => 'OR',
                'not'   => 'NOT',
            ],
        ],
        'input_b' => [
            'name' => 'Source B',
            'type' => 'source',
        ],
    ],
    'display' => [
        'name' => 'Comparison',
        'description' => 'Comparison and logical operators',
        'y-axis' => 'right',
        'top_level' => false,
    ],
    'normalize' => [
        'mode' => 'individual',
    ],
];
