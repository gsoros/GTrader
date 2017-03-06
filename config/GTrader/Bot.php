<?php

return [
    'signal_lifetime'   => 150,                         // how long a signal should be valid, in candles
    'user_options'      => [                            // User-configurable options
                                'unfilled_max' => 0,    // Cancel unfilled orders after this number of candles, 0 = disable
                            ],
];
