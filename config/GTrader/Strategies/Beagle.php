<?php

return [
    'history_table'     => 'training_history',
    'population'        => 10,
    'mutation_rate'     => 1,
    'max_nesting'       => 3,
    'loss_tolerance'    => 50,
    'memory_limit'      => 512, // MB
    'memory_reserve'    => 90, // percent
    'ranges' => [
        'test' => [
            'start_percent' => 50,
            'end_percent' => 100
        ],
    ],
];
