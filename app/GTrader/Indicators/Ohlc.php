<?php

namespace GTrader\Indicators;

use GTrader\Candle;

class Ohlc extends HasInputs
{
    public function key(string $output = '')
    {
        if ('ha' === $this->getParam('indicator.mode')) {
            return null;
        }
        $stripped = array_map(function ($v) {
            return substr($v, 6); // "input_"
        }, array_keys($inputs = $this->getInputs()));

        if (in_array($o = strtolower($output), $stripped)) {
            return $inputs['input_'.$o];
        }
        error_log($this->getShortClass().'::key() output: '.$o.' not in '.json_encode($inputs));
        return null;
    }


    public function getDisplaySignature(string $format = 'long')
    {
        $mode = $this->getParam('indicator.mode');
        $mode = $this->getParam('adjustable.mode.options.'.$mode, 'Candlestick');
        if ('short' === $format) {
            return $mode;
        }
        $param_str = $this->getParamString(['mode']);
        foreach ($this->getInputs() as $k => $v) {
            if (substr($k, 6) !== $v) {
                return $mode.' ('.$param_str.')' ;
            }
        }
        return $mode;
    }

    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function calculate(bool $force_rerun = false)
    {
        if ('ha' !== $this->getParam('indicator.mode')) {
            return false;
        }
        $sig = $this->getSignature();
        $candles = $this->getCandles();

        $key_open = $candles->key($sig.':::Open');
        $key_high = $candles->key($sig.':::High');
        $key_low = $candles->key($sig.':::Low');
        $key_close = $candles->key($sig.':::Close');

        reset($candles);

        $prev_c = null;
        foreach($candles as $c) {
            $new_c = $this->heikinashi($this->candle2arr($c), $this->candle2arr($prev_c));
            $c->$key_open = $new_c['open'];
            $c->$key_high = $new_c['high'];
            $c->$key_low = $new_c['low'];
            $c->$key_close = $new_c['close'];
            $prev_c = $c;
        }

        return $this;
    }



    protected function candle2arr(Candle $c = null)
    {
        if (is_null($c)) {
            return null;
        }
        return [
            'open' => $c->open,
            'high' => $c->high,
            'low' => $c->low,
            'close' => $c->close
        ];
    }

    protected function heikinashi(array $candle, array $prev_candle = null)
    {
        if (is_null($prev_candle)) {
            return $candle;
        }
        if (!isset($prev_candle['open']) ||
            !isset($prev_candle['high']) ||
            !isset($prev_candle['low']) ||
            !isset($prev_candle['close'])) {
            return $candle;
        }
        return [
            'open' => ($prev_candle['open'] + $prev_candle['close']) / 2,
            'high' => max($candle['open'], $candle['high'], $candle['close']),
            'low' => min($candle['low'], $candle['open'], $candle['close']),
            'close' => ($candle['open'] + $candle['high'] + $candle['low'] + $candle['close']) / 4
        ];
    }
}
