<?php

return [
    'long_name' => 'Dummy Exchange (for testing)',
    'short_name' => 'DMY',
    'local_name' => 'Dummy',                                // class name, also used in the local database
    'symbols' => [
        'dummy_symbol_1' =>                                 // used in the local database, same as local_name
            [
            'long_name' => 'Dummy Symbol 1',
            'short_name' => 'DMY1',                         // used for displaying in lists
            'local_name' => 'dummy_symbol_1',               // used in the local database, same as the key
            'remote_name' => 'dmy',                         // used when querying the remote data
            'resolutions'=> [180     => '3 minutes',
                             300     => '5 minutes'],
            ],
        'dummy_symbol_2' =>                                 // used in the local database, same as local_name
            [
            'long_name' => 'Dummy Symbol 2',
            'short_name' => 'DMY2',                         // used for displaying in lists
            'local_name' => 'dummy_symbol_2',               // used in the local database, same as the key
            'remote_name' => 'dmy',                         // used when querying the remote data
            'resolutions'=> [3600     => 'One hour'],
            ],
    ],



];
