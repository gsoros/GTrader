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
];
