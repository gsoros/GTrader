<?php

namespace GTrader\Indicators;

use GTrader\Log;

class Aroonosc extends Trader
{
    public function traderCalc(array $values)
    {
        if (!($values = trader_aroonosc(
            $values[$this->getInput('input_high')],
            $values[$this->getInput('input_low')],
            $this->getParam('indicator.period')
        ))) {
            Log::error('trader_aroonosc returned false');
            return [];
        }
        return [$values];
    }
}
