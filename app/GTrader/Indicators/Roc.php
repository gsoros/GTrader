<?php

namespace GTrader\Indicators;

/** Rate of change */
class Roc extends Trader
{

    public function traderCalc(array $values)
    {
        if (! $values = trader_roc($values[$this->getInput()])) {
            error_log('trader_ad returned false');
            return [];
        }
        return [$values];
    }
}
