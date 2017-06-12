<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;

/** Relative Stregnth Index */
class Rsi extends Trader
{

    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function traderCalc(array $values)
    {
        if (!($values = trader_rsi($values, $this->getParam('indicator.period')))) {
            error_log('trader_rsi returned false');
            return [];
        }
        return [$values];
    }
}
