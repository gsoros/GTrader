<?php

namespace GTrader\Indicators;

class Aroon extends Trader
{
    public function traderCalc(array $values)
    {
        if (!($values = trader_aroon(
            $values[$this->getInput('input_high')],
            $values[$this->getInput('input_low')],
            $this->getParam('indicator.period')
            ))) {
            error_log('trader_aroon returned false');
            return [];
        }
        return $values;
    }
}
