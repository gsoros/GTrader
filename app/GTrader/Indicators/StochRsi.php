<?php

namespace GTrader\Indicators;

use GTrader\Log;

/** Stochastic Relative Stregnth Index */
class StochRsi extends Trader
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
        if (!($values = trader_stochrsi(
            $values[$this->getInput()],
            $this->getParam('indicator.period'),
            $this->getParam('indicator.fastk'),
            $this->getParam('indicator.fastd'),
            $this->getParam('indicator.matype')
        ))) {
            Log::error('trader_stochrsi returned false');
            return [];
        }

        return $values;
    }
}
