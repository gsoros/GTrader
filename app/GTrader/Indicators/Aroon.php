<?php

namespace GTrader\Indicators;

use GTrader\Log;

class Aroon extends Trader
{
    public function traderCalc(array $values)
    {
        if (!($values = trader_aroon(
            $values[$this->getInput('input_high')],
            $values[$this->getInput('input_low')],
            $this->getParam('indicator.period')
        ))) {
            Log::error('trader_aroon returned false');
            return [];
        }
        return $values;
    }
}
