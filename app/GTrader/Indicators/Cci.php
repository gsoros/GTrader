<?php

namespace GTrader\Indicators;

class Cci extends Trader
{
    public function traderCalc(array $values)
    {
        if (!($values = trader_cci(
            $values[$this->getInput('input_high')],
            $values[$this->getInput('input_low')],
            $values[$this->getInput('input_close')],
            $this->getParam('indicator.period')
            ))) {
            error_log('trader_cci returned false');
            return [];
        }
        return [$values];
    }
}
