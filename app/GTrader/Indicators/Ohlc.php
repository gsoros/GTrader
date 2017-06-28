<?php

namespace GTrader\Indicators;

class Ohlc extends HasInputs
{
    public function key(string $output = '')
    {
        $stripped = array_map(function ($v) {
            return substr($v, 6); // "input_"
        }, array_keys($inputs = $this->getInputs()));

        if (in_array($o = strtolower($output), $stripped)) {
            return $o;
        }
        error_log('Ohlc::key() output: '.$output.' not in '.json_encode($inputs));
        return null;
    }


    public function getDisplaySignature(string $format = 'long')
    {
        $mode = $this->getParam('indicator.mode');
        $mode = $this->getParam('adjustable.mode.options.'.$mode, 'Candlestick');
        if ('short' === $format) {
            return $mode;
        }
        return ($param_str = $this->getParamString(['mode'])) ? $mode.' ('.$param_str.')' : $mode;
    }

    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function calculate(bool $force_rerun = false)
    {
        return $this;
    }
}
