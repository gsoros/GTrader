<?php

return [
    'indicator' =>  [
        'input_source' => 'open',
    ],
    'adjustable' => [
        'input_source' => [
            'name' => 'Source',
            'type' => 'source',
        ],
    ],
    'display' => [
        'name' => 'ROC',
        'description' => 'Rate Of Change',
        'y_axis_pos' => 'right',
        'top_level' => false,
    ],
    'fill_value' => 0,
    'normalize' => [
        'mode' => 'individual',
        'to' => 0,
    ],
];
