<?php

return [

    'children_ns'       => 'Indicators',
    'available'         => [    'Balance'           => ['allow_multiple' => false],
                                'Ema'               => ['allow_multiple' => true],
                                'FannPrediction'    => ['allow_multiple' => false],
                                'FannSignals'       => ['allow_multiple' => false],
                            ],
    'default_child'     => 'Ema',
    'display'           => [
                                'name'              => 'Unnamed Indicator',
                                'visible'           => true
                            ],
];
