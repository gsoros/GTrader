<?php

namespace GTrader\Indicators;

use GTrader\Log;

class Dmi extends Trader
{
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

        foreach ([$mode, 'plus_di', 'minus_di'] as $key) {
            $func = 'trader_'.$key;

            if (!function_exists($func)) {
                Log::error('Function not found: '.$func);
                return [];
            }

            if (!$values[] = call_user_func_array($func, $args)) {
                Log::error($func.' returned false');
                return [];
            }
        }
        return $values;
    }
}
