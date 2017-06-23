<?php

return [
    'indicator' => [
        'input_source' => 'open',
        'mode' => 'high',
        //'input_highRef' => '{"class":"Constant","params":{"name":"Zero","value":0}}',
        //'input_lowRef' => '{"class":"Constant","params":{"name":"Zero","value":0}}',
        'input_highRef' => 'open',
        'input_lowRef' => 'open',
        'inclusive' => false,
    ],
    'adjustable' => [
        'input_source' => [
            'name' => 'Source',
            'type' => 'source',
        ],
        'mode' => [
            'name' => 'Mode',
            'type' => 'select',
            'options' => [
                'high' => 'High pass',
                'low' => 'Low pass',
                'band' => 'Band pass',
            ],
        ],
        'input_highRef' => [
            'name' => 'High cutoff',
            'type' => 'source',
        ],
        'input_lowRef' => [
            'name' => 'Low cutoff',
            'type' => 'source',
        ],
        'inclusive' => [
            'name' => 'Inclusive',
            'type' => 'bool',
        ],
    ],
    'display' => [
        'name' => 'Pass',
        'description' => 'High/Low/Band Pass',
        'y_axis_pos' => 'right',
        'top_level' => false,
    ],

];
