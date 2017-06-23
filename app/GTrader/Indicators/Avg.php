<?php

namespace GTrader\Indicators;

use GTrader\Indicators\HasInputs;

/** Average */
class Avg extends HasInputs
{

    public function calculate(bool $force_rerun = false)
    {
        $this->runDependencies($force_rerun);

        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $input = $candles->key($this->getInput());

        $signature = $candles->key($this->getSignature());

        $total = 0;
        $count = 0;

        $candles->reset();
        while ($candle = $candles->next()) {

            if (!isset($candle->$input)) {
                error_log('Avg::calculate() '.$signature.' candle->'.$input.' is not set');
                break;
            }
            $total += $candle->$input;
            $count ++;

            $candle->$signature = $total / $count;

        }
        return $this;
    }
}
