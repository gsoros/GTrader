<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;
use GTrader\Series;

/** Hilbert Transform */
class Ht extends Trader
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    public function __wakeup()
    {
        parent::__wakeup();
        $this->init();
    }


    public function init()
    {
        $mode = $this->getParam('indicator.mode');
        if (!is_array($sel = $this->getParam('modes.'.$mode))) {
            error_log('Ht::init() mode not found: '.$mode);
            return $this;
        }
        if (isset($sel['display']['y_axis_pos'])) {
            $this->setParam('display.y_axis_pos', $sel['display']['y_axis_pos']);
        }
        $this->setParam('outputs', isset($sel['outputs']) ? $sel['outputs'] : ['']);

        return $this;
    }


    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function traderCalc(array $values)
    {
        $this->init();

        $mode = $this->getParam('indicator.mode');
        $in_a = $this->getParam('indicator.input_a');
        $in_b = $this->getParam('indicator.input_b');

        if (!is_array($sel = $this->getParam('modes.'.$mode))) {
            error_log('Ht::traderCalc() mode not found: '.$mode);
            return [];
        }

        $func = 'trader_ht_'.$mode;
        if (!function_exists($func)) {
            error_log('Ht::traderCalc() function not found: '.$func);
            return [];
        }

        $args = [$values[$in_a]];
        if (in_array('b', $sel['sources'])) {
            $args[] = $values[$in_b];
        }

        if (!$values = call_user_func_array($func, $args)) {
            error_log('Ht::traderCalc() '.$func.' returned false');
            return [];
        }
        //dd($values);
        //dd($this->getParams());
        return 1 < count($this->getParam('outputs', [])) ? $values : [$values];
    }


}
