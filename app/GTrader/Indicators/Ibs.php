<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

/** Internal Bar Strength */
class Ibs extends Indicator
{

    public function calculate(bool $force_rerun = false)
    {
        if (!($candles = $this->getCandles())) {
            return $this;
        }
        $signature = $candles->key($this->getSignature());
        $candles->reset();
        while ($candle = $candles->next()) {

            $candle->$signature =
                0 == ($div = $candle->high - $candle->low) ?
                .5 :
                ($candle->close - $candle->low) / $div;
        }
        return $this;
    }
}
