<?php

return [
    'indicator' => [
        'mode' => 'sine',
        'input_a' => 'open',
        'input_b' => 'close',
    ],
    'adjustable' => [
        'mode' => [
            'name' => 'Mode',
            'type' => 'select',
            'options' => [
                'dcperiod' => 'Dominant Cycle Period',
                'dcphase' => 'Dominant Cycle Phase',
                'phasor' => 'Phasor Components',
                'sine' => 'Sine Wave',
                'trendline' => 'Instantaneous Trendline',
                'trendmode' => 'Trend vs. Cycle Mode',
            ],
        ],
        'input_a' => [
            'name' => 'Source A',
            'type' => 'source',
        ],
        'input_b' => [
            'name' => 'Source B',
            'type' => 'source',
        ],
    ],
    'modes'=> [
        'dcperiod' => [
            'sources' => ['input_a'],
            'display' => ['y-axis' => 'right'],
            'normalize' => ['mode' => 'individual', 'to' => 0],
        ],
        'dcphase' => [
            'sources' => ['input_a'],
            'display' => ['y-axis' => 'right'],
            'normalize' => ['mode' => 'individual', 'to' => 0],
        ],
        'phasor' => [
            'sources' => ['input_a'],
            'display' => ['y-axis' => 'right'],
            'outputs' => ['A', 'B'],
            'normalize' => ['mode' => 'individual', 'to' => 0],
        ],
        'sine' => [
            'sources' => ['input_a'],
            'display' => ['y-axis' => 'right'],
            'outputs' => ['A', 'B'],
            'normalize' => ['mode' => 'individual', 'to' => 0],
        ],
        'trendline' => [
            'sources' => ['input_a'],
            'display' => ['y-axis' => 'input_a'],
            'normalize' => 'input_a',
        ],
        'trendmode' => [
            'sources' => ['input_a'],
            'display' => ['y-axis' => 'right'],
            'normalize' => [
                'mode' => 'range',
                'range' => [
                    'min' => 0,
                    'max' => 1,
                ],
            ],
        ],
    ],
    'display' => [
        'name' => 'HT',
        'description' => 'Hilbert Transform',
        'y-axis' => 'right',
        'top_level' => false,
    ],

];
