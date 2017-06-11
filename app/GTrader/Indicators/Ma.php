<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;

/** Moving Average */
class Ma extends Trader
{

    public function traderCalc(array $values)
    {
        if (!($values = trader_ma(
            $values,
            $this->getParam('indicator.length'),
            $this->getParam('indicator.type')))) {
            throw new \Exception('trader_ma returned false');
        }
        return $values;
    }
}
