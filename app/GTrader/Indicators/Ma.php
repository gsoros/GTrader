<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;

/** Moving Average */
class Ma extends Trader
{

    public function __construct()
    {
        $this->setParam(
            'adjustable.type.options',
            \Config::get('GTrader.Indicators.Trader.MA_TYPES')
        );
        parent::__construct();
    }

    public function getMaType()
    {
        return $this->getParam('indicator.type');
    }

    public function traderCalc(array $values)
    {
        if (!($values = trader_ma(
            $values,
            $this->getParam('indicator.length'),
            $this->getMaType()
        ))) {
            error_log('trader_ma returned false');
            return [];
        }
        return [$values];
    }
}
