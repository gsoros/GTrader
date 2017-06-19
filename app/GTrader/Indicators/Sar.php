<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;
use GTrader\Series;

/** Parabolic Stop And Reverse */
class Sar extends Trader
{
    public function hasBase()
    {
        return false;
    }
/*
    public function basedOn(string $target_base)
    {
        // Trick fann to include this indicator in the most recent candle
        if ('open' === $target_base) {
            return true;
        }
        return false;
    }
*/

    protected function trader_sarext(array $high, array $low)
    {
        return trader_sarext(
            $high,
            $low,
            $this->getParam('indicator.start'),
            $this->getParam('indicator.offset'),
            $this->getParam('indicator.accelInitLong'),
            $this->getParam('indicator.accelLong'),
            $this->getParam('indicator.accelMaxLong'),
            $this->getParam('indicator.accelInitShort'),
            $this->getParam('indicator.accelShort'),
            $this->getParam('indicator.accelMaxShort')
        );
    }

    public function traderCalc(array $values)
    {
        $new_values = [];

        $lookback = intval($this->getParam('indicator.simulation_lookback'));
        if (1 < $lookback) {
            if ($open_count = count($values['open'])) {
                $ocml = $open_count - $lookback;
                for ($i = 0; $i < $ocml; $i++) {
                    $high = array_slice($values['high'], $i, $lookback);
                    array_push($high, $values['open'][$i + $lookback]);
                    $low = array_slice($values['low'], $i, $lookback);
                    array_push($low, $values['open'][$i + $lookback]);
                    $sar = $this->trader_sarext($high, $low);
                    if (count($new_values)) {
                        array_push($new_values, array_pop($sar));
                        continue;
                    }
                    $new_values = $sar;
                }
                //error_log('Sar with lookback: '.json_encode($new_values));
            }
        }

        if (!count($new_values)) {
            if (!($new_values = $this->trader_sarext($values['high'], $values['low']))) {
                error_log('trader_sarext() returned false');
                return [];
            }
        }

        foreach ($new_values as $k => $v) {
            if ($v < 0) {
                $new_values[$k] = -$v;
            }
        }
        return [$new_values];
    }

    public function extract(Series $candles)
    {
        $values = [
            'high' => $candles->extract('high'),
            'low' => $candles->extract('low'),
        ];

        if (1 < intval($this->getParam('indicator.simulation_lookback'))) {
            $values['open'] = $candles->extract('open');
        }

        return $values;
    }
}
