<?php

namespace GTrader\Indicators;

class Abs extends HasInputs
{
    public function calculate(bool $force_rerun = false)
    {
        $this->runInputIndicators($force_rerun);

        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $input = $candles->key($this->getInput());
        $output = $candles->key($this->getSignature());

        $candles->reset();
        while ($candle = $candles->next()) {
            if (null === ($val = $candle->$input)) {
                continue;
            }
            $candle->$output = abs(floatval($val));
        }

        return $this;
    }
}
