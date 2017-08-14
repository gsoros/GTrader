<?php

namespace GTrader\Indicators;

use GTrader\Log;

class Cmo extends Trader
{
    public function traderCalc(array $values)
    {
        if (!($values = trader_cmo(
            $values[$this->getInput('input_source')],
            $this->getParam('indicator.period')
        ))) {
            Log::error('trader_cmo returned false');
            return [];
        }
        return [$values];
    }
}
