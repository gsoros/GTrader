<?php

return [

    'children_ns' => 'Indicators',
    'available' => [
        'Balance'           => ['allow_multiple' => false],
        'Ma'                => ['allow_multiple' => true],
        'Ema'               => ['allow_multiple' => true],
        'FannPrediction'    => ['allow_multiple' => false],
        'FannSignals'       => ['allow_multiple' => false],
        'Profitability'     => ['allow_multiple' => false],
        'Avg'               => ['allow_multiple' => true],
        'Rsi'               => ['allow_multiple' => true],
        'Stoch'             => ['allow_multiple' => true],
        'StochRsi'          => ['allow_multiple' => true],
        'Macd'              => ['allow_multiple' => true],
        'Bbands'            => ['allow_multiple' => true],
    ],
    'outputs' => [''],
    'normalize_type' => 'ohlc',
    'default_child' => 'Ma',
    'display' => [
        'name'              => 'Unnamed Indicator',
        'visible'           => true,
    ],
];
