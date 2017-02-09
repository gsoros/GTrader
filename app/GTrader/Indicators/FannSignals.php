<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Series;

class FannSignals extends Indicator
{
    protected $_allowed_owners = ['GTrader\\Strategies\\Fann'];
                    

    public function calculate()
    {
        $signature = $this->getSignature();

        $owner = $this->getOwner();
        $candles = $this->getCandles();

                
        $indicator = 'FannPrediction';
        if (!$owner->hasIndicator($indicator))
            $owner->addIndicator($indicator, ['display' => ['visible' => false]]);
        $owner->getIndicator($indicator)->checkAndRun();
        
        $prediction_ema = $owner->getParam('prediction_ema');
        if ($prediction_ema >= 1) 
        {
            $ema = Indicator::make('Ema', 
                            ['indicator' => ['price' => $indicator, 'length' => $prediction_ema],
                             'display' => ['visible' => false]]);
            if (!$candles->hasIndicator($ema->getSignature()))
                $candles->addIndicator($ema);
            $ema = $candles->getIndicator($ema->getSignature());
            $ema->checkAndRun();
            //dd($candles);
        }

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
            if (isset($candle->$indicator)) 
            {   
                // skip trade if last trade was recent
                if ($last['time'] >= $candle->time - $min_distance * $resolution) 
                    continue;
                
                $price_buy = $price_sell = $candle->open;
                
                if ($candle->$indicator > $candle->open + $candle->open / $long_threshold &&
                                                    ($last['signal'] != 'buy' || $spitfire))
                {
                    $candle->$signature = ['signal' => 'buy', 'price' => $price_buy];
                    $last = ['time' => $candle->time, 'signal' => 'buy'];
                }
                else if ($candle->$indicator < $candle->open - $candle->open / $short_threshold &&
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
