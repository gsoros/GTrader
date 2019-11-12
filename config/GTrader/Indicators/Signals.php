<?php

$generate_group = function($key, $action) {

    $generate_input = function($name, $group_label, $group_desc, $cols) {
        return [
            'name' => $name,
            'type' => 'source',
            'display' => [
                'hide' => ['label'],
                'group' => [
                    'label' => $group_label,
                    'description' => $group_desc,
                    'cols' => $cols,
                ],
            ],
        ];
    };

    $generate_cond = function($name, $group_label, $cols) {
        return [
            'name' => $name,
            'type' => 'select',
            'options' => [
                '<'     => '<',
                '<='    => '<=',
                '>'     => '>',
                '>='    => '>=',
                'and'   => 'AND',
                '||'    => 'OR',
                '!='    => 'NOT',
            ],
            'display' => [
                'hide' => ['label'],
                'group' => [
                    'label' => $group_label,
                    'cols' => $cols,
                ],
            ],
        ];
    };

    $generate_source = function($name, $desc) {
        return [
            'name' => $name,
            'type' => 'source',
            'immutable' => true,   // do not mutate this setting
            'description' => $desc,
            'display' => [
                'group' => [
                    'hide' => ['label'],
                    'label' => $name,
                    'cols' => 8,
                ],
            ],
        ];
    };

    return [
        'input_'.$key.'_a' => $generate_input(
            $action.' A',
            $action,
            'Generate an \''.$action.'\' signal by comparing two sources',
            4
        ),
        $key.'_cond' => $generate_cond(
            $action.' Condition',
            $action,
            2
        ),
        'input_'.$key.'_b' => $generate_input(
            $action.' B',
            $action,
            '',
            4
        ),
        'input_'.$key.'_source' => $generate_source(
            'Source',
            'Source for the \''.$action.'\' signal price. Used in back-testing and if the exchange is configured to use limit orders.'
        ),
    ];
};

return [
    'indicator' => [
        'strategy_id'               => -1,
        'input_open_long_a'         => 'open',
        'open_long_cond'            => '>',
        'input_open_long_b'         => 'open',
        'input_open_long_source'    => 'open',
        'input_close_long_a'        => 'open',
        'close_long_cond'           => '<',
        'input_close_long_b'        => 'open',
        'input_close_long_source'   => 'open',
        'input_open_short_a'        => 'open',
        'open_short_cond'           => '<',
        'input_open_short_b'        => 'open',
        'input_open_short_source'   => 'open',
        'input_close_short_a'       => 'open',
        'close_short_cond'          => '>',
        'input_close_short_b'       => 'open',
        'input_close_short_source'  => 'open',
        'min_trade_distance'        => 1,
        'use_incomplete_candle'    => false,
    ],
    'adjustable' => array_merge(
        [
            'strategy_id' => [
                'name' => 'Strategy',
                'type' => 'select',
                'options' => [
                    -1 => 'Automatic From Parent',
                    0 => 'Custom Settings',
                ],
                'immutable' => true,      // do not mutate this setting
            ],
        ],
        $generate_group('open_long','Open Long'),
        $generate_group('close_long','Close Long'),
        $generate_group('open_short','Open Short'),
        $generate_group('close_short','Close Short'),
        [
            'min_trade_distance' => [
                'name' => 'Min Trade Distance',
                'type' => 'int',
                'min' => 1,
                'max' => 100,
                'display' => [
                    'label_cols' => 3,
                    'group' => [
                        'hide' => ['label'],
                        'label' => 'Misc. options',
                        'cols' => 2,
                    ],
                ],
            ],
            'use_incomplete_candle' => [
                'name' => 'Use incomplete candle',
                'description' => 'When checked, the conditions will be evaluated using the most recent and probably incomplete candle. Warning: this will skew the backtesting, use only if all input is from open.',
                'type' => 'bool',
                'immutable' => true,      // do not mutate this setting
                'display' => [
                    'hide' => ['label'],
                    'group' => [
                        'label' => 'Misc. options',
                        'cols' => 5,
                    ],
                ],
            ],
        ],
    ),
    'display' =>  [
        'name' => 'Signals',
        'description' => 'Displays the signals from a strategy',
        'mode' => 'linepoints',
        'top_level' => false,
        'y-axis' => 'left',
    ],
    'outputs' => [
        'signal',
        'price',
    ],
];
