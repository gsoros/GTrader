<?php

namespace GTrader\Indicators;

use GTrader\Log;

/** Rate of change */
class Roc extends Trader
{
    public function traderCalc(array $values)
    {
        if (! $values = trader_roc($values[$this->getInput()])) {
            Log::error('trader_roc returned false');
            return [];
        }
        return [$values];
    }
}
