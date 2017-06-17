<?php

namespace GTrader;

use GTrader\Chart;

abstract class Indicator implements \JsonSerializable
{
    use Skeleton, HasOwner
    {
        Skeleton::__construct as private __skeletonConstruct;
    }

    protected $calculated = false;


    public function __construct(array $params = [])
    {
        $this->__skeletonConstruct($params);

        $this->allowed_owners = ['GTrader\\Series', 'GTrader\\Strategy'];

        if (!$this->getParam('display.y_axis_pos')) {
            $this->setParam('display.y_axis_pos', 'left');
        }
    }


    public function __clone()
    {
        $this->calculated = false;
    }


    public function jsonSerialize()
    {
        //return get_object_vars($this);
        return [
            'class' => get_class($this),
            'params' => $this->getParam('indicator'),
        ];
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
                    if ('float' === $this->getParam('adjustable.'.$key.'.type')) {
                        $value = str_replace('.', 'd', $value);
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
        $params = $this->getParam('adjustable');
        //$param_str = (is_array($params)) ? join(', ', $params) : null;

        $param_str = '';
        if (is_array($params)) {
            if (count($params)) {
                foreach ($params as $key => $value) {
                    if (strlen($param_str)) {
                        $param_str .= ', ';
                    }
                    if (isset($value['type'])) {
                        if ('select' === $value['type']) {
                            if (isset($value['options'])) {
                                if ($selected = $value['options'][$this->getParam('indicator.'.$key)]) {
                                    $param_str .= $selected;
                                    continue;
                                }
                            }
                        }
                    }
                    $param_str .= ucfirst(explode('_', $this->getParam('indicator.'.$key))[0]);
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
            //error_log($this->getSignature().' has already run');
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


    public static function getClassFromSignature(string $signature)
    {
        return explode('_', $signature)[0];
    }


    public static function getParamsFromSignature(string $signature)
    {
        $pieces = explode('_', $signature);
        // First element is the class
        $indicator = Indicator::make(array_shift($pieces));
        if (!count($pieces)) {
            return [];
        }
        $params = [];
        $key = null;
        while (list($junk, $piece) = each($pieces)) {
            if (is_null($key)) {
                $key = $piece;
                continue;
            }
            if ('float' === $indicator->getParam('adjustable.'.$key.'.type')) {
                $piece = floatval(str_replace('d', '.', $piece));
                //error_log('Indicator::getParamsFromSignature() float '.$key.' = '.$piece);
            }
            $params[$key] = $piece;
            $key = null;
        }
        return ['indicator' => $params];
    }


    public function getLastValue(bool $force_rerun = false)
    {
        $sig = $this->getSignature();
        $this->checkAndRun($force_rerun);
        if ($last = $this->getCandles()->last()) {
            return $last->$sig;
        }
        return 0;
    }


    public function getForm(array $params = [])
    {
        return view('Indicators/Form',
            array_merge($params, ['indicator' => $this])
        );
    }

    public function getNormalizeType()
    {
        return $this->getParam('normalize_type');
    }

    public function hasBase()
    {
        return false;
    }
}
