<?php

namespace GTrader\Indicators;

use GTrader\Log;

class MinMax extends HasInputs
{
    public function getDisplaySignature(
      string $format = 'long',
      string $output = null,
      array $overrides = [])
    {
        $op = $this->getParam('indicator.operation');
        $op = $this->getParam('adjustable.operation.options.'.$op, 'MinMax');
        if ('short' === $format) {
            return $op;
        }
        return ($param_str = $this->getParamString(['operation'])) ? $op.' ('.$param_str.')' : $op;
    }


    public function calculate(bool $force_rerun = false)
    {
        $this->beforeCalculate();

        $this->runInputIndicators($force_rerun);

        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $key_in = $candles->key($this->getInput('input_a'));
        $key_out = $candles->key($this->getSignature());

        $func = $this->getParam('indicator.operation');
        if (!function_exists($func)) {
            Log::error('func does not exist', $func);
            return $this;
        }

        $candles->resetToKey($candles->getFirstKeyForDisplay());
        while ($candle = $candles->next()) {
            if (!isset($candle->$key_in)) {
                continue;
            }
            if (!isset($min_max)) {
                $min_max = $candle->$key_in;
            }
            //Log::debug($min_max, $candle->$key);
            $candle->$key_out = $min_max = floatval($func($min_max, $candle->$key_in));
        }

        return $this;
    }
}
