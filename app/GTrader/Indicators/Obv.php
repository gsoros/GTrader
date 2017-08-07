<?php

namespace GTrader\Indicators;

use GTrader\Series;

/** On Balance Volume */
class Obv extends Trader
{
    public function traderCalc(array $values)
    {
        if (!($values = trader_obv($values['input'], $values['volume']))) {
            Log::error('trader_obv returned false');
            return [];
        }
        return [$values];
    }

    public function extract(Series $candles, string $index_type = 'sequential')
    {
        return [
            'input' => $candles->extract($candles->key($this->getInput()), $index_type),
            'volume' => $candles->extract('volume', $index_type),
        ];
    }
}
