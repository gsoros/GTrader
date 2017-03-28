<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

/**  Exponential Moving Average */
class Ema extends Indicator
{
    protected $allowed_owners = ['GTrader\\Series'];


    public function calculate(bool $force_rerun = false)
    {
        $params = $this->getParam('indicator');

        $length = intval($params['length']);
        $price = $params['price'];

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
            $candle_price = 0;
            if (isset($candle->$price)) {
                $candle_price = $candle->$price;
            } else {
                error_log('Ema::calculate() '.$signature.' candle->'.$price.' is not set');
            }
            //echo 'candle: '; dump($candle);
            $prev_candle = $candles->prev();
            //echo 'prev candle: '; dump($prev_candle);
            if (is_object($prev_candle)) {
                $prev_candle_sig = 0;
                if (isset($prev_candle->$signature)) {
                    $prev_candle_sig = $prev_candle->$signature;

                } else {
                    // TODO handle the error
                    //throw new \Exception('Ema: prev_candle->'.$signature.' is not set');
                }
                // calculate current ema
                $candle->$signature =
                    ($candle_price - $prev_candle_sig) * (2 / ($length + 1))
                    + $prev_candle_sig;
            } else {
                // start with the first candle's price as a basis for the ema
                $candle->$signature = $candle_price;
            }
            //$candles->set($candle);
        }
        //dd($candles);
        return $this;
    }
}
