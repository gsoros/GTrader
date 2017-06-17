<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;
use GTrader\Series;

/** Stochastic Oscillator */
class Stoch extends Trader
{

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setParam(
            'adjustable.slowkmatype.options',
            \Config::get('GTrader.Indicators.Trader.MA_TYPES')
        );
        $this->setParam(
            'adjustable.slowdmatype.options',
            \Config::get('GTrader.Indicators.Trader.MA_TYPES')
        );
    }

    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function hasBase()
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
            error_log('trader_stoch returned false');
            return [];
        }

        return $values;
    }

    public function extract(Series $candles)
    {
        return [
            'high' => $candles->extract('high'),
            'low' => $candles->extract('low'),
            'close' => $candles->extract('close'),
        ];
    }
}
