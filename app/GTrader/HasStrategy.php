<?php

namespace GTrader;

use GTrader\Strategy;

trait HasStrategy
{
    protected $_strategy;


    public function getStrategy()
    {
        //if (!is_object($this->_strategy))
        //    $this->_strategy = Strategy::make();
        return $this->_strategy;
    }

    public function setStrategy(Strategy &$strategy)
    {
        if (is_callable([$this, 'getCandles']))
        {
            $candles = $this->getCandles();
            $strategy->setCandles($candles);
        }
        $this->_strategy = $strategy;
        return $this;
    }
}
