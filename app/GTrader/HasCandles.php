<?php

namespace GTrader;

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
            throw new \Exception('$this->_candles is not an object.');
        return $this->_candles;
    }
    
    public function unsetCandles() 
    {
        unset($this->_candles);
        return $this;
    }
}
