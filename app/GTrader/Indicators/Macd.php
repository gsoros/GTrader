<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;

/** Stochastic Relative Stregnth Index */
class Macd extends Trader
{

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        foreach (['fast', 'slow', 'signal'] as $type) {
            $this->setParam(
                'adjustable.'.$type.'matype.options',
                \Config::get('GTrader.Indicators.Trader.MA_TYPES')
            );
        }
    }

    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function traderCalc(array $values)
    {
        if (!($values = trader_macdext(
            $values[$this->getInput()],
            $this->getParam('indicator.fastperiod'),
            $this->getParam('indicator.fastmatype'),
            $this->getParam('indicator.slowperiod'),
            $this->getParam('indicator.slowmatype'),
            $this->getParam('indicator.signalperiod'),
            $this->getParam('indicator.signalmatype')
        ))) {
            error_log('trader_macdext returned false');
            return [];
        }
        //error_log('Macd: '.json_encode($values[0]));
        return $values;
    }
}
