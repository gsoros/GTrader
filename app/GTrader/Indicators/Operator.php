<?php

namespace GTrader\Indicators;

class Operator extends HasInputs
{
    public function getDisplaySignature(string $format = 'long', string $output = null)
    {
        $op = $this->getParam('indicator.operation');
        $op = $this->getParam('adjustable.operation.options.'.$op, 'Operator');
        if ('short' === $format) {
            return $op;
        }
        return ($param_str = $this->getParamString(['operation'])) ? $op.' ('.$param_str.')' : $op;
    }

    protected function operate(float $a, float $b)
    {
        switch ($this->getParam('indicator.operation')) {
            case 'add':
                return $a + $b;
            case 'sub':
                return $a - $b;
            case 'mult':
                return $a * $b;
            case 'div':
                return $b != 0 ? $a / $b : null;
            case 'perc':
                return $a * $b / 100;
        }
        return 0;
    }

    public function calculate(bool $force_rerun = false)
    {
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
                $val = $this->operate(
                    floatval($candle->$key_a),
                    floatval($candle->$key_b)
                );
                //error_log($candle->$key_a.' '.$this->getParam('indicator.operation').' '.$candle->$key_b.' = '.$val);
            }
            $candle->$key_out = $val;
            //dd($candle->$key_out);
        }

        return $this;
    }
}
