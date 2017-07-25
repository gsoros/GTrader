<?php

return [
    // number of concurrent training processes
    'slots'         => env('TRAINING_MANAGER_SLOTS', 2),

    // number of seconds to wait between checks for an open slot
    'wait_for_slot' => 15,

    // to run trainings with low process and io priority, you could add the following line to your .env
    // TRAINING_MANAGER_PHP_COMMAND="/usr/bin/ionice -c 3 /usr/bin/nice -n 19 /usr/bin/php"
    'php_command'   => env('TRAINING_MANAGER_PHP_COMMAND', 'php'),
];
