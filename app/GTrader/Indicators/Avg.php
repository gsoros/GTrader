<?php

namespace GTrader\Indicators;

use GTrader\Log;

/** Average */
class Avg extends HasInputs
{
    public function calculate(bool $force_rerun = false)
    {
        $this->beforeCalculate();
        
        $this->runInputIndicators($force_rerun);

        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $input = $candles->key($this->getInput());
        $output = $candles->key($this->getSignature());

        $total = 0;
        $count = 0;

        $candles->reset();
        while ($candle = $candles->next()) {
            if (!isset($candle->$input)) {
                Log::error('candle->'.$input.' is not set');
                break;
            }
            $total += $candle->$input;
            $count ++;

            $candle->$output = $total / $count;
        }
        return $this;
    }
}
