<?php

namespace GTrader\Indicators;

/** Internal Bar Strength */
class Ibs extends HasInputs
{
    public function getInputs()
    {
        return ['high', 'low', 'close'];
    }

    public function calculate(bool $force_rerun = false)
    {
        if (!($candles = $this->getCandles())) {
            return $this;
        }
        $key = $candles->key($this->getSignature());
        $candles->reset();
        while ($candle = $candles->next()) {

            $candle->$key =
                0 == ($div = $candle->high - $candle->low) ?
                .5 :
                ($candle->close - $candle->low) / $div;
        }
        return $this;
    }
}
