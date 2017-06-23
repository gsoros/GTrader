<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;
use GTrader\Series;

/** On Balance Volume */
class Obv extends Trader
{
    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function traderCalc(array $values)
    {
        if (!($values = trader_obv($values['input'], $values['volume']))) {
            error_log('trader_obv returned false');
            return [];
        }
        return [$values];
    }

    public function extract(Series $candles)
    {
        return [
            'input' => $candles->extract($candles->key($this->getInput())),
            'volume' => $candles->extract('volume'),
        ];
    }
}
