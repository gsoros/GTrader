<?php

return [
    'children_ns' => 'Indicators',
    'default_child' => 'Ma',
    'outputs' => ['default'],
    'normalize' => [
        'mode' => 'ohlc',
    ],
    'display' => [
        'mode' => 'line',
        'name' => 'Unnamed Indicator',
        'visible' => false,
        'z-index' => 0,
        'auto-y-axis' => false,
        'outputs' => ['all'],
        'editable_outputs' => ['all'],
        'colors' => [],
    ],
];
