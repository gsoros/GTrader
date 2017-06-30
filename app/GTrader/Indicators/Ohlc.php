<?php

namespace GTrader\Indicators;

use GTrader\Candle;

class Ohlc extends HasInputs
{
    public function key(string $output = '')
    {
        if ('line' === $this->getParam('indicator.mode')) {
            return $this->getCandles()->key($this->getInput());
        }
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


    public function getInput(string $name = null)
    {
        if ('line' === $this->getParam('indicator.mode')) {
            return $this->getParam('indicator.input_open');
        }
        return parent::getInput($name);
    }

    public function getOutputs()
    {
        if ('line' === $this->getParam('indicator.mode')) {
            return [''];
        }
        return parent::getOutputs();
    }

    public function getDisplaySignature(string $format = 'long')
    {
        $mode = $this->getParam('indicator.mode');
        $mode_label = $this->getParam('adjustable.mode.options.'.$mode, 'Candlesticks');
        if ('line' === $mode) {
            $except = ['mode', 'input_high', 'input_low', 'input_close'];
            $mode_label = 'Price';
        }
        else if ('candlestick' === $mode) {
            $except = ['mode'];
            $mode_label = 'OHLC';
        }
        else if ('ha' === $mode) {
            $except = ['mode'];
        }
        if ('short' === $format) {
            return $mode_label;
        }
        $param_str = $this->getParamString($except);
        foreach ($this->getInputs() as $k => $v) {
            if (substr($k, 6) !== $v) { // not default?
                return $mode_label.' ('.$param_str.')' ;
            }
        }
        return $mode_label;
    }

    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function calculate(bool $force_rerun = false)
    {
        if ('ha' !== $mode = $this->getParam('indicator.mode', 'candlestick')) {
            $this->setParam('display.mode', $mode);
            return false;
        }
        $this->setParam('display.mode', 'candlestick');
        $sig = $this->getSignature();
        $candles = $this->getCandles();

        $in_key_open = $candles->key($this->getInput('input_open'));
        $in_key_high = $candles->key($this->getInput('input_high'));
        $in_key_low = $candles->key($this->getInput('input_low'));
        $in_key_close = $candles->key($this->getInput('input_close'));

        $out_key_open = $candles->key($this->getSignature().':::Open');
        $out_key_high = $candles->key($this->getSignature().':::High');
        $out_key_low = $candles->key($this->getSignature().':::Low');
        $out_key_close = $candles->key($this->getSignature().':::Close');

        reset($candles);

        $prev_c = null;
        foreach($candles as $c) {

            $new_c = $this->heikinashi(
                $this->candle2arr($c, $in_key_open, $in_key_high, $in_key_low, $in_key_close),
                $this->candle2arr($prev_c, $in_key_open, $in_key_high, $in_key_low, $in_key_close)
            );

            $c->$out_key_open = $new_c['open'];
            $c->$out_key_high = $new_c['high'];
            $c->$out_key_low = $new_c['low'];
            $c->$out_key_close = $new_c['close'];

            $prev_c = $c;
        }

        return $this;
    }



    protected function candle2arr(Candle $c = null, $key_open, $key_high, $key_low, $key_close)
    {
        if (is_null($c)) {
            return null;
        }
        return [
            'open' => $c->$key_open,
            'high' => $c->$key_high,
            'low' => $c->$key_low,
            'close' => $c->$key_close,
        ];
    }

    protected function heikinashi(array $in_1, array $in_0 = null)
    {
        if (is_null($in_0)) {
            return $in_1;
        }
        if (!isset($in_0['open']) ||
            !isset($in_0['high']) ||
            !isset($in_0['low']) ||
            !isset($in_0['close'])) {
            return $in_1;
        }
        return [
            'open' => ($in_0['open'] + $in_0['close']) / 2,
            'high' => max($in_1['open'], $in_1['high'], $in_1['close']),
            'low' => min($in_1['low'], $in_1['open'], $in_1['close']),
            'close' => ($in_1['open'] + $in_1['high'] + $in_1['low'] + $in_1['close']) / 4
        ];
    }
}
