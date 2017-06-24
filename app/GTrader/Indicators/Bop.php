<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;

/** Balance Of Power */
class Bop extends Trader
{

    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function traderCalc(array $values)
    {
        if (!($values = trader_bop(
            $values[$this->getInput('input_open')],
            $values[$this->getInput('input_high')],
            $values[$this->getInput('input_low')],
            $values[$this->getInput('input_close')]
            ))) {
            error_log('trader_bop returned false');
            return [];
        }
        return [$values];
    }
}
