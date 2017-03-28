<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Series;

class FannSignals extends Indicator
{
    protected $allowed_owners = ['GTrader\\Strategies\\Fann'];


    public function createDependencies()
    {
        $owner = $this->getOwner();
        if (is_object($owner)) {
            /* just calling the owner's method will create the dependency */
            $owner->getPredictionIndicator();
        }
        return $this;
    }


    public function calculate(bool $force_rerun = false)
    {
        $signature = $this->getSignature();

        $owner = $this->getOwner();
        $candles = $this->getCandles();

        $indicator = $this->getOwner()->getPredictionIndicator();
        $indicator->checkAndRun($force_rerun);
        $indicator_sig = $indicator->getSignature();

        $last = ['time' => 0, 'signal' => ''];
        $candles_seen = 0;

        //$trade_indicator = 'open';
        //$trade_indicator = 'ohlc4';
        //$this->candles->ohlc4()->reset();

        $spitfire = $owner->getParam('spitfire');
        $long_threshold = $owner->getParam('prediction_long_threshold');
        $short_threshold = $owner->getParam('prediction_short_threshold');
        if ($long_threshold == 0 || $short_threshold == 0) {
            throw new \Exception('Threshold is zero');
        }
        $min_distance = $owner->getParam('min_trade_distance');
        $resolution = $candles->getParam('resolution');
        $num_input = $owner->getNumInput();

        $candles->reset();
        while ($candle = $candles->next()) {
            if ($force_rerun && isset($candle->$signature)) {
                unset($candle->$signature);
            }

            $candles_seen++;
            if ($candles_seen < $num_input) {
                // skip trading while inside the first sample
                continue;
            }
            if (isset($candle->$indicator_sig)) {
                // skip trade if last trade was recent
                if ($last['time'] >= $candle->time - $min_distance * $resolution) {
                    continue;
                }

                $price_long = $price_short = $candle->open;

                if ($candle->$indicator_sig >
                            $candle->open + $candle->open / $long_threshold &&
                            ($last['signal'] != 'long' || $spitfire)) {
                    $candle->$signature = ['signal' => 'long', 'price' => $price_long];
                    $last = ['time' => $candle->time, 'signal' => 'long'];
                } elseif ($candle->$indicator_sig <
                            $candle->open - $candle->open / $short_threshold &&
                            ($last['signal'] != 'short' || $spitfire)) {
                    $candle->$signature = ['signal' => 'short', 'price' => $price_short];
                    $last = ['time' => $candle->time, 'signal' => 'short'];
                }
            }
        }
        //dd($candles);
        //error_log('strategy signals: '.count($signals));
        return $this;
    }
}
