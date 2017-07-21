<?php

namespace GTrader\Indicators;

/** Relative Stregnth Index */
class Rsi extends Trader
{

    public function traderCalc(array $values)
    {
        if (!($values = trader_rsi(
            $values[$this->getInput()],
            $this->getParam('indicator.period')))
            ) {
            error_log('trader_rsi returned false');
            return [];
        }
        return [$values];
    }
}
