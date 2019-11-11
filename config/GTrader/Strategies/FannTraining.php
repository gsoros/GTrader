<?php

return [
    'reset_after'           => 0,
    'suffix'                => '.train',
    'max_boredom'           => 10,   // increase number of epochs between tests after this number of epochs without improvement
    'max_epoch_jump'        => 100,  // max number of epochs between tests
    'max_regression' => [            // allow this percentage of regression from max
        'test' => 5,
        'verify' => 5,
    ],
    'crosstrain'            => 0,
    'ranges' => [
        'train' => [
            'start_percent' => 0,
            'end_percent' => 30
        ],
        'test' => [
            'start_percent' => 30,
            'end_percent' => 60
        ],
        'verify' => [
            'start_percent' => 60,
            'end_percent' => 80
        ],
    ],
    'progress' => [
        'view' => [
            'epoch' => [
                'label' => 'Epoch',
                'title' => 'Current epoch / Last improvement at',
                'format' => '{{epoch}} / {{last_improvement_epoch}}',
                'items' => [
                    'epoch' => 'int',
                    'last_improvement_epoch' => 'int',
                ],
            ],
            'test' => [
                'label' => 'Test',
                'title' => 'Current / Best',
                'format' => '{{test}} / {{test_max}}',
                'items' => [
                    'test' => 'float',
                    'test_max' => 'float',
                ],
            ],
            'train_mser' => [
                'label' => 'Train MSER',
                'title' => 'Mean Squared Error Recipocal',
                'format' => '{{train_mser}}',
                'items' => [
                    'train_mser' => 'float',
                ],
            ],
            'verify' => [
                'label' => 'Verify',
                'title' => 'Current / Best',
                'format' => '{{verify}} / {{verify_max}}',
                'items' => [
                    'verify' => 'float',
                    'verify_max' => 'float',
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
            'epoch_jump' => [
                'label' => 'Jump',
                'title' => 'Number of epoch between tests',
                'format' => '{{epoch_jump}}',
                'items' => [
                    'epoch_jump' => 'int',
                ],
            ],
        ],
    ],
];
