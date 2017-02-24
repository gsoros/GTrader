<?php

namespace GTrader;

use GTrader\Series;

trait HasCandles
{
    protected $_candles;


    public function setCandles(Series &$candles)
    {
        $this->_candles = $candles;
        return $this;
    }


    public function getCandles()
    {
        if (!is_object($this->_candles))
        {
            $candles = new Series();
            $this->setCandles($candles);
        }
        return $this->_candles;
    }


    public function unsetCandles()
    {
        unset($this->_candles);
        return $this;
    }
}
