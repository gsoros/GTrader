<?php

namespace GTrader\Indicators;

/** Exponential Moving Average */
class Ema extends Ma
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->unsetParam('indicator.type')
            ->unsetParam('adjustable.type');
    }

    public function getMaType()
    {
        return TRADER_MA_TYPE_EMA;
    }
}
