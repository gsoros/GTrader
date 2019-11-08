<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

// (A + B) / 2
class Mid extends HasInputs
{

    public function calculate(bool $force_rerun = false)
    {
        $this->beforeCalculate();

        $this->runInputIndicators($force_rerun);

        if (!($candles = $this->getCandles())) {
            return $this;
        }
        $key_a = $candles->key($this->getInput('input_a'));
        $key_b = $candles->key($this->getInput('input_b'));
        $key = $candles->key($this->getSignature());

        $candles->reset();
        while ($candle = $candles->next()) {
            $candle->$key = (($candle->$key_a ?? 0) + ($candle->$key_b ?? 0)) / 2;
        }
        return $this;
    }
}
