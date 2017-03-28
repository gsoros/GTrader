<?php

namespace GTrader;

use GTrader\Strategy;

trait HasStrategy
{
    protected $strategy;


    public function getStrategy()
    {
        return $this->strategy;
    }

    public function setStrategy(Strategy &$strategy)
    {
        if (is_callable([$this, 'getCandles'])) {
            $candles = $this->getCandles();
            $strategy->setCandles($candles);
        }
        $this->strategy = $strategy;
        return $this;
    }
}
