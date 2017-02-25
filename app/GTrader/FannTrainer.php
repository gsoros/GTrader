<?php

namespace GTrader;

use GTrader\Strategies\Fann as FannStrategy;

class FannTrainer extends Skeleton
{
    protected $strategy;

    public function __construct(FannStrategy $strategy)
    {
        $this->strategy = $strategy;
    }
}
