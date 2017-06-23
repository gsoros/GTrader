<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;

/** Bollinger Bands */
class Bbands extends Trader
{

    public function __construct(array $params = [])
    {
        parent::__construct($params);

        $this->setParam(
            'adjustable.matype.options',
            \Config::get('GTrader.Indicators.Trader.MA_TYPES')
        );
    }

/*
    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }
*/

    public function traderCalc(array $values)
    {
        if (!($values = trader_bbands(
            $values[$this->getInput()],
            $this->getParam('indicator.period'),
            $this->getParam('indicator.devup'),
            $this->getParam('indicator.devdown'),
            $this->getParam('indicator.matype')
        ))) {
            error_log('trader_bbands returned false');
            return [];
        }

        return $values;
    }
}
