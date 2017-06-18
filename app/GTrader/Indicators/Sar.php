<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;
use GTrader\Series;

/** Parabolic Stop And Reverse */
class Sar extends Trader
{

    public function basedOn(string $target_base)
    {
        // Trick fann to include this indicator in the most recent candle
        if ('open' === $target_base) {
            return true;
        }
        return false;
    }

    public function traderCalc(array $values)
    {
        if (!($values = trader_sarext(
            $values['high'],
            $values['low'],
            $this->getParam('indicator.start'),
            $this->getParam('indicator.offset'),
            $this->getParam('indicator.accelInitLong'),
            $this->getParam('indicator.accelLong'),
            $this->getParam('indicator.accelMaxLong'),
            $this->getParam('indicator.accelInitShort'),
            $this->getParam('indicator.accelShort'),
            $this->getParam('indicator.accelMaxShort')
        ))) {
            error_log('trader_sarext() returned false');
            return [];
        }
        foreach ($values as $k => $v) {
            if ($v < 0) {
                $values[$k] = -$v;
            }
        }
        return [$values];
    }

    public function extract(Series $candles)
    {
        return [
            'high' => $candles->extract('high'),
            'low' => $candles->extract('low'),
        ];
    }
}
