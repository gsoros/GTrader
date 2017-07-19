<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

class Constant extends Indicator
{
    public function getNormalizeParams()
    {
        return array_replace_recursive(
            parent::getNormalizeParams(), [
                'mode' => 'individual',
                'to' => $this->getParam('indicator.value'),
            ]
        );
    }

    public function getDisplaySignature(string $format = 'long')
    {
        $value = $this->getParam('indicator.value', 0);
        return ('short' === $format) ? $value : $this->getParam('display.name').' ('.$value.')';
    }

    public function calculate(bool $force_rerun = false)
    {
        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $key = $candles->key($this->getSignature());
        $value = $this->getParam('indicator.value');

        $candles->reset();
        while ($candle = $candles->next()) {
            $candle->$key = $value;
        }
        return $this;
    }
}
