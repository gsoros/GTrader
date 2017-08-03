<?php

namespace GTrader\Indicators;

class Ohlc extends HasInputs
{
    public function init()
    {
        return $this;
    }

    public function key(string $output = '')
    {
        if (!$output) {
            dd('Ohlc::key() no output', debug_backtrace());
        }
        $mode = $this->getParam('indicator.mode');
        // line returns open
        if ('linepoints' === $mode) {
            return $this->getParam('indicator.input_open', 'open');
        }
        // candlesticks and ohlc: input = output
        if (in_array($mode, ['candlestick', 'ohlc'])) {
            if ($output) {
                return $this->getParam('indicator.input_'.$output, $output);
            }
        }
        // heikin-ashi modifies the output
        return null;
    }



    public function getInput(string $name = null)
    {
        if ('linepoints' === $this->getParam('indicator.mode')) {
            return $this->getParam('indicator.input_open');
        }
        return parent::getInput($name);
    }



    public function getInputs()
    {
        if ('linepoints' === $this->getParam('indicator.mode')) {
            return [$this->getInput()];
        }
        return parent::getInputs();
    }

    public function getOutputs()
    {
        if ('linepoints' === $this->getParam('indicator.mode')) {
            return ['open'];
        }
        return parent::getOutputs();
    }

    public function outputDependsOn(array $sigs = [], string $output = null)
    {
        // Pretend that our outputs depend 1:1 on the matching input
        $output = ($output = strtolower($output)) ? $output : 'open';
        $input = $this->getInput('input_'.$output);
        if (in_array($input, $sigs)) {
            return true;
        }
        if (!$owner = $this->getOwner()) {
            return false;
        }
        return $owner->indicatorOutputDependsOn($input, $sigs);
    }


    public function getDisplaySignature(string $format = 'long', string $output = null)
    {
        $mode = $this->getParam('indicator.mode');
        if ('linepoints' === $mode) {
            return 'Open';
        } elseif ('candlestick' === $mode) {
            return $output ? ucfirst($output) : 'Candles';
        }
        $mode_name = $this->getParam('adjustable.mode.options.'.$mode, 'Candlesticks');
        return $output ? $mode_name.' => '.ucfirst($output) : $mode_name;
    }



    public function calculate(bool $force_rerun = false)
    {
        if ('ha' !== $mode = $this->getParam('indicator.mode', 'candlestick')) {
            $this->setParam('display.mode', $mode);
            return $this;
        }
        $this->setParam('display.mode', 'candlestick');
        //$sig = $this->getSignature(); // unused
        $candles = $this->getCandles();

        $in_key_open = $candles->key($this->getInput('input_open'));
        $in_key_high = $candles->key($this->getInput('input_high'));
        $in_key_low = $candles->key($this->getInput('input_low'));
        $in_key_close = $candles->key($this->getInput('input_close'));

        $out_key_open = $candles->key($this->getSignature('open'));
        $out_key_high = $candles->key($this->getSignature('high'));
        $out_key_low = $candles->key($this->getSignature('low'));
        $out_key_close = $candles->key($this->getSignature('close'));

        reset($candles);

        $prev_c = null;
        foreach ($candles as $c) {
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



    protected function candle2arr($c, $key_open, $key_high, $key_low, $key_close)
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
        if (is_null($in_0) ||
            !isset($in_0['open']) ||
            !isset($in_0['high']) ||
            !isset($in_0['low']) ||
            !isset($in_0['close'])) {
            return [
                'open' => ($in_1['open'] + $in_1['close']) / 2,
                'high' => $in_1['high'],
                'low' => $in_1['low'],
                'close' => ($in_1['open'] + $in_1['high'] + $in_1['low'] + $in_1['close']) / 4,
            ];
        }
        $open = ($in_0['open'] + $in_0['close']) / 2;
        $close = ($in_1['open'] + $in_1['high'] + $in_1['low'] + $in_1['close']) / 4;
        $high = max($in_1['high'], $open, $close);
        $low = min($in_1['low'], $open, $close);

        return [
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $close,
        ];
    }
}
