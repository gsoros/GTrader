<?php

return [
    'reset_after'           => 0,
    'suffix'                => '.train',
    'max_boredom'           => 10,   // increase jump size after this number of uneventful epochs
    'epoch_jump_max'        => 100,  // max amount of epochs between tests
    'test_regression'       => .9,   // allow this amount of regression to test max
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
