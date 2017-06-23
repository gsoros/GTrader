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
            'display' => ['y_axis_pos' => 'right'],
        ],
        'dcphase' => [
            'sources' => ['input_a'],
            'display' => ['y_axis_pos' => 'right'],
        ],
        'phasor' => [
            'sources' => ['input_a', 'input_b'],
            'display' => ['y_axis_pos' => 'right'],
            'outputs' => ['A', 'B'],
        ],
        'sine' => [
            'sources' => ['input_a', 'input_b'],
            'display' => ['y_axis_pos' => 'right'],
            'outputs' => ['A', 'B'],
        ],
        'trendline' => [
            'sources' => ['input_a'],
            'display' => ['y_axis_pos' => 'left'],
        ],
        'trendmode' => [
            'sources' => ['input_a'],
            'display' => ['y_axis_pos' => 'right'],
        ],
    ],
    'display' => [
        'name' => 'HT',
        'description' => 'Hilbert Transform',
        'y_axis_pos' => 'right',
        'top_level' => false,
    ],

];
