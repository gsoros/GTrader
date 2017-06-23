<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;
use GTrader\Series;

/** Parabolic Stop And Reverse */
class Sar extends Trader
{

    public function getInputs()
    {
        return [];
    }

    public function inputFrom(string $signature)
    {
        // Trick fann to include this indicator in the most recent candle
        if ('open' === $signature) {
            return $this->getLookBack() ? true : false;
        }
        return false;
    }

    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

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

    protected function getLookBack()
    {
        return 1 < ($lookback = intval($this->getParam('indicator.simulationLookback'))) ? $lookback : false;

    }

    protected function simulatedSar($values)
    {
        if (!($lookback = $this->getLookBack())) {
            return false;
        }
        if (!($open_count = count($values['open']))) {
            return false;
        }

        $new_values = [];
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

        return count($new_values) ? $new_values : false;
    }

    public function traderCalc(array $values)
    {
        if (!($new_values = $this->simulatedSar($values))) {
            if (!($new_values = $this->trader_sarext($values['high'], $values['low']))) {
                return [];
            }
        }

        foreach ($new_values as $k => $v) {
            if ($v < 0) { // faster than abs()
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

        if ($this->getLookBack()) {
            $values['open'] = $candles->extract('open');
        }

        return $values;
    }
}
