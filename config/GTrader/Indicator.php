<?php

return [

    'children_ns'       => 'Indicators',
    'available'         => [    'Balance'           => ['allow_multiple' => false],
                                'Ema'               => ['allow_multiple' => true],
                                'FannPrediction'    => ['allow_multiple' => false],
                                'FannSignals'       => ['allow_multiple' => false],
                                'Profitability'     => ['allow_multiple' => false],
                                'Avg'               => ['allow_multiple' => true],
                            ],
    'default_child'     => 'Ema',
    'display'           => [
                                'name'              => 'Unnamed Indicator',
                                'visible'           => true
                            ],
];
