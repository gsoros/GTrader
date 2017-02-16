<?php

namespace GTrader;

use GTrader\Chart;

abstract class Indicator extends Skeleton
{
    use HasOwner;

    protected $_calculated = false;


    public function __construct()
    {
        parent::__construct();

        if (!$this->getParam('display.y_axis_pos'))
            $this->setParam('display.y_axis_pos', 'left');
    }


    public abstract function calculate();


    public function __wakeup()
    {
        $this->_calculated = false;
    }


    public function getSignature()
    {
        $class = $this->getShortClass();
        $params = $this->getParam('indicator');
        $param_str = count($params) ? join('_', $params) : null;
        return $param_str ? $class.'_'.$param_str : $class;
    }

    public function getDisplaySignature()
    {
        $name = $this->getParam('display.name');
        $param_arr = [];
        $p = $this->getParam('indicator');
        if (is_array($p))
            foreach ($p as $k => $v)
                $param_arr[] = $k.': '.$v;
        $param_str = count($param_arr) ? join(', ', $param_arr) : null;
        return $param_str ? $name.' ('.$param_str.')' : $name;
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
