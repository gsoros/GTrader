<?php

namespace GTrader\Indicators;

use GTrader\Series;

use GTrader\Log;

/** Stochastic Oscillator */
class Stoch extends Trader
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setParam(
            'adjustable.slowkmatype.options',
            config('GTrader.Indicators.Trader.MA_TYPES')
        );
        $this->setParam(
            'adjustable.slowdmatype.options',
            config('GTrader.Indicators.Trader.MA_TYPES')
        );
    }

    public function hasInputs()
    {
        return false;
    }

    public function traderCalc(array $values)
    {
        if (!($values = trader_stoch(
            $values['high'],
            $values['low'],
            $values['close'],
            $this->getParam('indicator.fastkperiod'),
            $this->getParam('indicator.slowkperiod'),
            $this->getParam('indicator.slowkmatype'),
            $this->getParam('indicator.slowdperiod'),
            $this->getParam('indicator.slowdmatype')
        ))) {
            Log::error('trader_stoch returned false');
            return [];
        }

        return $values;
    }

    public function extract(Series $candles, string $index_type = 'sequential')
    {
        return [
            'high' => $candles->extract('high', $index_type),
            'low' => $candles->extract('low', $index_type),
            'close' => $candles->extract('close', $index_type),
        ];
    }
}
