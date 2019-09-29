<?php
return [
    'message_type'  => 3, // append to file
    'destination'   => env('GTRADER_LOG_FILE', storage_path('logs/GTrader.log')),
];
