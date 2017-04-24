<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Avg;

/**  Exponential Moving Average */
class Ema extends Avg
{

    public function calculate(bool $force_rerun = false)
    {
        $this->runDependencies($force_rerun);

        $params = $this->getParam('indicator');

        $length = intval($params['length']);
        $base = $params['base'];

        if ($length <= 1) {
            error_log('Ema needs int length > 1');
            return $this;
        }

        $signature = $this->getSignature();

        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $candles->reset();
        while ($candle = $candles->next()) {
            $candle_base = 0;
            if (!isset($candle->$base)) {
                error_log('Ema::calculate() '.$signature.' candle->'.$base.' is not set');
                return $this;
            }
            $candle_base = $candle->$base;
            $prev_candle = $candles->prev();
            if (!is_object($prev_candle)) {
                // start with the first candle's base as a basis for the ema
                $candle->$signature = $candle_base;
                continue;
            }
            $prev_candle_sig = 0;
            if (!isset($prev_candle->$signature)) {
                error_log('Ema: prev_candle->'.$signature.' is not set');
            }
            $prev_candle_sig = $prev_candle->$signature;
            // calculate current ema
            $candle->$signature =
                ($candle_base - $prev_candle_sig) * (2 / ($length + 1))
                + $prev_candle_sig;
        }
        return $this;
    }
}
