<?php

namespace GTrader\Indicators;

use GTrader\Log;

class Comparison extends HasInputs
{
    public function getDisplaySignature(
      string $format = 'long',
      string $output = null,
      array $overrides = [])
    {
        $op = $this->getParam('indicator.operation');
        $op = $this->getParam('adjustable.operation.options.'.$op, '=');
        if ('short' === $format) {
            return $op;
        }
        $input_a = $this->getParamString(['operation', 'input_b']);
        $input_b = $this->getParamString(['operation', 'input_a']);
        return $input_a.' '.$op.' '.$input_b;
    }


    protected function operate(float $a, float $b)
    {
        switch ($this->getParam('indicator.operation')) {
            case 'lt':
                return $a < $b;
            case 'lte':
                return $a <= $b;
            case 'gt':
                return $a > $b;
            case 'gte':
                return $a >= $b;
            case 'and':
                return $a && $b;
            case 'or':
                return $a || $b;
            case 'not':
                return $a != $b;
            case 'eq':
            default:
                return $a == $b;
        }
        return 0;
    }


    public function calculate(bool $force_rerun = false)
    {
        $this->beforeCalculate();

        $this->runInputIndicators($force_rerun);

        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $key_a = $candles->key($this->getInput('input_a'));
        $key_b = $candles->key($this->getInput('input_b'));
        $key_out = $candles->key($this->getSignature());

        $candles->reset();
        while ($candle = $candles->next()) {
            $val = null;
            if (isset($candle->$key_a) && isset($candle->$key_b)) {
                $val = floatval(
                    $this->operate(
                        floatval($candle->$key_a),
                        floatval($candle->$key_b)
                    )
                );
                //Log::debug($candle->$key_a.' '.$this->getParam('indicator.operation').' '.$candle->$key_b.' = '.$val);
            }
            $candle->$key_out = $val;
            //dd($candle->$key_out);
        }

        return $this;
    }
}
