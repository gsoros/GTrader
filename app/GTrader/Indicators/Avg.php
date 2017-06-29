<?php

namespace GTrader\Indicators;

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

        $key = $candles->key($this->getSignature());

        $total = 0;
        $count = 0;

        $candles->reset();
        while ($candle = $candles->next()) {

            if (!isset($candle->$input)) {
                error_log('Avg::calculate() '.$key.' candle->'.$input.' is not set');
                break;
            }
            $total += $candle->$input;
            $count ++;

            $candle->$key = $total / $count;

        }
        return $this;
    }
}
