<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Series;

class FannSignals extends Indicator
{
    protected $_allowed_owners = ['GTrader\\Strategies\\Fann'];


    public function createDependencies()
    {
        $owner = $this->getOwner();
        if (is_object($owner))
            $owner->getPredictionIndicator(); /* just calling the owner's method will create the dependency */
        return $this;
    }


    public function calculate()
    {
        $signature = $this->getSignature();

        $owner = $this->getOwner();
        $candles = $this->getCandles();

        $indicator = $this->getOwner()->getPredictionIndicator();
        $indicator->checkAndRun();
        $indicator_sig = $indicator->getSignature();

        $last = ['time' => 0, 'signal' => ''];
        $candles_seen = 0;

        //$trade_indicator = 'open';
        //$trade_indicator = 'ohlc4';
        //$this->_candles->ohlc4()->reset();

        $spitfire = $owner->getParam('spitfire');
        $long_threshold = $owner->getParam('prediction_long_threshold');
        $short_threshold = $owner->getParam('prediction_short_threshold');
        if ($long_threshold == 0 || $short_threshold == 0)
            throw new \Exception('Threshold is zero');
        $min_distance = $owner->getParam('min_trade_distance');
        $resolution = $candles->getParam('resolution');

        $candles->reset();
        while ($candle = $candles->next())
        {
            $candles_seen++;
            if ($candles_seen < $owner->getNumInput()) continue; // skip trading while inside the first sample
            if (isset($candle->$indicator_sig))
            {
                // skip trade if last trade was recent
                if ($last['time'] >= $candle->time - $min_distance * $resolution)
                    continue;

                $price_buy = $price_sell = $candle->open;

                if ($candle->$indicator_sig > $candle->open + $candle->open / $long_threshold &&
                                                    ($last['signal'] != 'buy' || $spitfire))
                {
                    $candle->$signature = ['signal' => 'buy', 'price' => $price_buy];
                    $last = ['time' => $candle->time, 'signal' => 'buy'];
                }
                else if ($candle->$indicator_sig < $candle->open - $candle->open / $short_threshold &&
                                                    ($last['signal'] != 'sell' || $spitfire))
                {
                    $candle->$signature = ['signal' => 'sell', 'price' => $price_sell];
                    $last = ['time' => $candle->time, 'signal' => 'sell'];
                }
            }
        }
        //dd($candles);
        //error_log('strategy signals: '.count($signals));
        return $this;
    }
}
