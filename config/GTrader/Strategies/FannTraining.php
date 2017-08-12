<?php

return [
    'reset_after'           => 0,
    'suffix'                => '.train',
    'max_boredom'           => 10,   // increase jump size after this number of uneventful epochs
    'epoch_jump_max'        => 100,  // max amount of epochs between tests
    'test_regression'       => .9,   // allow this amount of regression to test max
    'crosstrain'            => 0,
];
