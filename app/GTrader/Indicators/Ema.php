<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Ma;

/** Exponential Moving Average */
class Ema extends Ma
{
    public function __construct()
    {
        parent::__construct();
        $this->unSetParam('indicator.type')
            ->unSetParam('adjustable.type');
    }

    public function getMaType()
    {
        return TRADER_MA_TYPE_EMA;
    }
}
