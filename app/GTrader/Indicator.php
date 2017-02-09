<?php

namespace GTrader;

use GTrader\Chart;

abstract class Indicator extends Skeleton
{
    use HasOwner;
    
    protected $_calculated = false;

    public abstract function calculate();

        
    
    public function getSignature()
    {
        $reflect = new \ReflectionClass($this);
        $class = $reflect->getShortName();
        $params = $this->getParam('indicator');
        $param_str = count($params) ? join('_', $params) : null;
        return $param_str ? $class.'_'.$param_str : $class;
    }
    
    
    public function getCandles()
    {
        return $this->getOwner()->getCandles();
    }


    public function setCandles(Series &$candles)
    {
        return $this->getOwner()->setCandles($candles);
    }
    

    public function checkAndRun(bool $force_rerun = false)
    {
        if (!$force_rerun && $this->_calculated)
            return $this;
        $this->_calculated = true;
        return $this->calculate();
    }


}
