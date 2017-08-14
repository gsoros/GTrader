<?php

namespace GTrader\Indicators;

use GTrader\Log;

/** Relative Stregnth Index */
class Rsi extends Trader
{
    public function traderCalc(array $values)
    {
        if (!($values = trader_rsi(
            $values[$this->getInput()],
            $this->getParam('indicator.period')
        ))
            ) {
            Log::error('trader_rsi returned false');
            return [];
        }
        return [$values];
    }
}
