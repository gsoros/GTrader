<?php

return [
    'schedule_frequency' => env('BOT_SCHEDULE_FREQ', 1), // Run bots once every minute
    'user_options'      => [            // User-configurable options
        'unfilled_max' => 0,            // Cancel unfilled orders after this number of candles, 0 = disable
    ],
];
