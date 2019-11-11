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
    'progress' => [
        'view' => [
            'epoch' => [
                'label' => 'Generation',
                'title' => 'Current generation / Last improvement at',
                'format' => '{{epoch}} / {{last_improvement_epoch}}',
                'items' => [
                    'epoch' => 'int',
                    'last_improvement_epoch' => 'int',
                ],
            ],
            'test' => [
                'label' => 'Test',
                'title' => 'Current / Best',
                'format' => '{{generation_best}} / {{father}}',
                'items' => [
                    'generation_best' => 'float',
                    'father' => 'float',
                ],
            ],
            'signals' => [
                'label' => 'Signals',
                'title' => 'Number of signals in test period',
                'format' => '{{signals}}',
                'items' => [
                    'signals' => 'int',
                ],
            ],
            'no_improvement' => [
                'label' => 'Last',
                'title' => 'Current epoch minus last improvement epoch',
                'format' => '{{no_improvement}}',
                'items' => [
                    'no_improvement' => 'int',
                ],
            ],
        ],
    ],
];
