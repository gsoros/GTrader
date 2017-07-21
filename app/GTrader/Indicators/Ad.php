<?php

namespace GTrader\Indicators;

/** Chaikin A/D Line */
class Ad extends Trader
{
    public function traderCalc(array $values)
    {
        if (!($values = trader_ad(
            $values[$this->getInput('input_high')],
            $values[$this->getInput('input_low')],
            $values[$this->getInput('input_close')],
            $values[$this->getInput('input_volume')]))) {
            error_log('trader_ad returned false');
            return [];
        }
        return [$values];
    }
}
