<?php

namespace GTrader\Indicators;

use GTrader\Log;

/* (Max) gain / (max) loss */
class GainLoss extends HasInputs
{
    public function getDisplaySignature(
      string $format = 'long',
      string $output = null,
      array $overrides = [])
    {
        $mode = $this->getParam('indicator.mode');
        $mode = $this->getParam('adjustable.mode.options.'.$mode, 'GainLoss');
        if ($this->getParam('indicator.maximum')) {
            $mode = 'Max'.$mode;
        }
        if ('short' === $format) {
            return $mode;
        }
        return ($param_str = $this->getParamString(['mode', 'maximum'])) ? $mode.' ('.$param_str.')' : $mode;
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

        $func = ('gain' === ($mode = $this->getParam('indicator.mode'))) ? 'min': 'max';

        $candles->resetToKey($candles->getFirstKeyForDisplay());
        while ($candle = $candles->next()) {
            if (!isset($candle->$key_in)) {
                continue;
            }
            $val = $candle->$key_in;
            if (!isset($min_max)) {
                $min_max = $val;
            }
            $min_max = floatval($func($min_max, $val));
            $numerator = ('gain' === $mode) ? ($val - $min_max) : ($min_max - $val);
            $current_gain_loss_percent = (0 === $min_max) ? 0 : $numerator / $min_max * 100;
            if (!$this->getParam('indicator.maximum')) {
                $candle->$key_out = $current_gain_loss_percent;
                continue;
            }
            if (!isset($max_gain_loss_percent)) {
                $max_gain_loss_percent = $current_gain_loss_percent;
            }
            $max_gain_loss_percent = max($max_gain_loss_percent, $current_gain_loss_percent);
            $candle->$key_out = $max_gain_loss_percent;
        }

        return $this;
    }
}
