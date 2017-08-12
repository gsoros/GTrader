<?php

return [
    'children_ns'           => 'Strategies',
    'default_child'         => env('STRATEGY_DEFAULT', 'Simple'),
    'available'             => ['Fann', 'Simple'], //, 'Tiktaalik'],
    'spitfire'              => false,
];
