<?php

namespace GTrader\Indicators;

use GTrader\Log;

/** Bollinger Bands */
class Bbands extends Trader
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        $this->setParam(
            'adjustable.matype.options',
            config('GTrader.Indicators.Trader.MA_TYPES')
        );
    }

    public function traderCalc(array $values)
    {
        if (!($values = trader_bbands(
            $values[$this->getInput()],
            $this->getParam('indicator.period'),
            $this->getParam('indicator.devup'),
            $this->getParam('indicator.devdown'),
            $this->getParam('indicator.matype')
        ))) {
            Log::error('trader_bbands returned false');
            return [];
        }

        return $values;
    }
}
