<?php

return [
    'children_ns'           => 'Strategies',
    'default_child'         => env('STRATEGY_DEFAULT', 'Fann'),
    'available'             => ['Fann', 'Simple'],
    'spitfire'              => false,
];
