<?php

namespace GTrader\Indicators;

use GTrader\Log;

/** Chaikin A/D Oscillator */
class Adosc extends Trader
{
    public function traderCalc(array $values)
    {
        if (!($values = trader_adosc(
            $values[$this->getInput('input_high')],
            $values[$this->getInput('input_low')],
            $values[$this->getInput('input_close')],
            $values[$this->getInput('input_volume')],
            $this->getParam('indicator.fastperiod'),
            $this->getParam('indicator.slowperiod')
        ))) {
            Log::error('trader_adosc returned false');
            return [];
        }
        return [$values];
    }
}
