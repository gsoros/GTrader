<?php

return [
    'indicator' => [
        'strategy_id' => -1,
    ],
    'adjustable' => [
        'strategy_id' => [
            'name' => 'Strategy',
            'type' => 'select',
            'options' => [
                -1 => 'Automatic From Parent',
            ],
        ],
    ],
    'display' =>  [
        'name' => 'Prediction',
        'y-axis' => 'left',
    ],
];
