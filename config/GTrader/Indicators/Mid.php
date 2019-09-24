<?php

return [
    'display' => [
        'name' => 'Mid',
        'description' => '(A + B) / 2',
    ],
    'adjustable' => [
        'input_a' => [
            'name' => 'Source A',
            'type' => 'source',
        ],
        'input_b' => [
            'name' => 'Source B',
            'type' => 'source',
        ],
    ],
    'indicator' => [
        'input_a' => 'open',
        'input_b' => 'close',
    ],

];
