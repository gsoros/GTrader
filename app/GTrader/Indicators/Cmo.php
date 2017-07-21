<?php

namespace GTrader\Indicators;

class Cmo extends Trader
{
    public function traderCalc(array $values)
    {
        if (!($values = trader_cmo(
            $values[$this->getInput('input_source')],
            $this->getParam('indicator.period')
            ))) {
            error_log('trader_cmo returned false');
            return [];
        }
        return [$values];
    }
}
