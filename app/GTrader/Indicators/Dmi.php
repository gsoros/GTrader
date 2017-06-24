<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;

class Dmi extends Trader
{

    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function traderCalc(array $values)
    {
        $mode = $this->getParam('indicator.mode');

        $args = [
            $values[$this->getInput('input_high')],
            $values[$this->getInput('input_low')],
            $values[$this->getInput('input_close')],
            $this->getParam('indicator.period'),
        ];
        $values = [];

        foreach ([
            $mode => 'dx',
            'plus_di' => 'plus',
            'minus_di' => 'minus'] as $k => $output) {

            $func = 'trader_'.$k;

            if (!function_exists($func)) {
                error_log('Dmi::traderCalc() function not found: '.$func);
                return [];
            }

            if (!$values[] = call_user_func_array($func, $args)) {
                error_log('Dmi::traderCalc() '.$func.' returned false');
                return [];
            }
        }
        return $values;
    }
}
