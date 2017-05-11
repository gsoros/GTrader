<?php

namespace GTrader;

use GTrader\Chart;

abstract class Indicator
{

    use Skeleton, HasOwner
    {
        Skeleton::__construct as private __skeletonConstruct;
    }

    protected $calculated = false;


    public function __construct(array $params = [])
    {
        $this->__skeletonConstruct($params);

        if (!$this->getParam('display.y_axis_pos')) {
            $this->setParam('display.y_axis_pos', 'left');
        }
    }


    abstract public function calculate(bool $force_rerun = false);


    public function __wakeup()
    {
        $this->calculated = false;
    }


    public function getSignature()
    {
        $class = $this->getShortClass();
        $params = $this->getParam('indicator');
        //$param_str = count($params) ? join('_', $params) : null;

        $param_str = '';
        if (is_array($params)) {
            if (count($params)) {
                foreach ($params as $key => $value) {
                    if (strlen($param_str)) {
                        $param_str .= '_';
                    }
                    $param_str .= $key.'_'.str_replace('_', '-', $value);
                }
            }
        }

        return $param_str ? $class.'_'.$param_str : $class;
    }


    public function getDisplaySignature()
    {
        $name = $this->getParam('display.name');
        $params = $this->getParam('indicator');
        //$param_str = (is_array($params)) ? join(', ', $params) : null;

        $param_str = '';
        if (is_array($params)) {
            if (count($params)) {
                foreach ($params as $value) {
                    if (strlen($param_str)) {
                        $param_str .= ', ';
                    }
                    $param_str .= explode('_', $value)[0];
                }
            }
        }

        return $param_str ? $name.' ('.$param_str.')' : $name;
    }


    public function getCandles()
    {
        if ($owner = $this->getOwner()) {
            return $owner->getCandles();
        }
        return null;
    }


    public function setCandles(Series &$candles)
    {
        return $this->getOwner()->setCandles($candles);
    }


    public function createDependencies()
    {
        return $this;
    }


    public function checkAndRun(bool $force_rerun = false)
    {
        if (!$force_rerun && $this->calculated) {
            return $this;
        }

        $depends = $this->getParam('depends');
        if (is_array($depends)) {
            if (count($depends)) {
                foreach ($depends as $indicator) {
                    $indicator->checkAndRun($force_rerun);
                }
            }
        }

        $this->calculated = true;
        return $this->calculate($force_rerun);
    }


    public static function getClassFromSignature($sig)
    {
        return explode('_', $sig)[0];
    }


    public static function getParamsFromSignature($sig)
    {
        $pieces = explode('_', $sig);
        // First elem is the class
        array_shift($pieces);
        if (!count($pieces)) {
            return [];
        }
        $params = [];
        $key = false;
        while (list($junk, $piece) = each($pieces)) {
            if (!$key) {
                $key = $piece;
                continue;
            }
            $params[$key] = $piece;
            $key = false;
        }
        return ['indicator' => $params];
    }

}
